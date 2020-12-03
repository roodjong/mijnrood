<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\{ Response, Request };
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use App\Entity\{ Document, DocumentFolder };
use App\Form\Documents\{ UploadType, NewFolderType };

class DocumentsController extends AbstractController
{

    /**
     * @Route("/documenten/{folderId<\d+>}", name="member_documents", defaults={ "folderId": "" })
     */
    public function documents(Request $requset, $folderId): Response {
        $folder = $this->getFolder($folderId);

        $folders = $this->getDoctrine()->getRepository(DocumentFolder::class)->findByParent($folder);
        $documents = $this->getDoctrine()->getRepository(Document::class)->findByFolder($folder);

        $canCreateFolder = $canUpload = $canDelete = $this->isGranted('ROLE_ADMIN');

        $uploadForm = $this->createForm(UploadType::class, null, ['action' => $this->generateUrl('member_documents_upload')]);
        $newFolderForm = $this->createForm(NewFolderType::class, null, ['action' => $this->generateUrl('member_documents_create_folder')]);

        return $this->render('user/documents.html.twig', [
            'folders' => $folders,
            'documents' => $documents,
            'folder' => $folder,
            'breadcrumbs' => $this->getBreadcrumbs($folder),

            'canUpload' => $canUpload,
            'canDelete' => $canDelete,
            'canCreateFolder' => $canCreateFolder,

            'uploadForm' => $uploadForm->createView(),
            'newFolderForm' => $newFolderForm->createView()
        ]);
    }

    /**
     * @Route("/documenten/download/{documentId<\d+>}", name="member_documents_download")
     */
    public function download(Request $request, $documentId): Response {
        $document = $this->getDoctrine()->getRepository(Document::class)->find($documentId);
        if ($document === null) {
            throw $this->createNotFoundException('Het opgevraagde document is niet beschikbaar.');
        }

        $response = new Response();
        $response->headers->set('Content-Disposition', 'attachment; filename="'.urlencode($document->getName()).'"');
        $response->headers->set('Content-Type', 'application/octet-stream');
        $response->setContent(file_get_contents($this->getParameter('documents_directory').'/'.$document->getUploadFileName()));
        return $response;
    }

    /**
     * @Route("/documenten/nieuwe-map/{folderId<\d+>}", name="member_documents_create_folder", defaults={ "folderId": "" })
     */
    public function createFolder(Request $request, $folderId): Response {
        $member = $this->getUser();
        $canUpload = $this->isGranted('ROLE_ADMIN');
        if (!$canUpload)
            throw $this->createNotFoundException('De opgevraagde bestandsmap is niet beschikbaar.');

        $folder = $this->getFolder($folderId);

        $form = $this->createForm(NewFolderType::class);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();

            $newFolder = new DocumentFolder();
            $newFolder->setName($form['name']->getData());
            $newFolder->setParent($folder);
            $newFolder->setMemberCreated($member);

            $em->persist($newFolder);
            $em->flush();

            if ($request->isXmlHttpRequest()) {
                return $this->json(['status' => 'success', 'id' => $newFolder->getId()]);
            }

            return $this->redirectToRoute('member_documents', ['folderId' => $newFolder->getId()]);
        }

        throw $this->createNotFoundException();
    }

    /**
     * @Route("/documenten/upload/{folderId<\d+>}", name="member_documents_upload", defaults={ "folderId": "" })
     */
    public function upload(Request $request, $folderId): Response {
        $member = $this->getUser();
        $canUpload = $this->isGranted('ROLE_ADMIN');
        if (!$canUpload)
            throw $this->createNotFoundException('De opgevraagde bestandsmap is niet beschikbaar.');

        $folder = $this->getFolder($folderId);

        $form = $this->createForm(UploadType::class);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();

            $documentsFolder = $this->getParameter('documents_directory');
            if (!is_dir($documentsFolder))
                mkdir($documentsFolder);

            $errors = [];
            $documents = [];

            foreach ($form['file']->getData() as $file) {
                try {
                    $fileSize = $file->getSize();
                    $fileName = $file->getClientOriginalName();
                    $hashedFileName = sha1($fileName.time());

                    $file->move($documentsFolder, $hashedFileName);

                    $document = new Document();
                    $document->setFolder($folder);
                    $document->setFileName($fileName);
                    $document->setSizeInBytes($fileSize);
                    $document->setMemberUploaded($member);
                    $document->setUploadFileName($hashedFileName);

                    $em->persist($document);
                    $documents[] = $document;
                } catch (\Exception $ex) {
                    $errors[] = [$file->getClientOriginalName(), $ex];
                }
            }

            $em->flush();

            if ($request->isXmlHttpRequest()) {
                $ids = array_map(fn($document) => $document->getId(), $documents);
                $errors = array_map(fn($error) => [$error[0], $error[1]->getMessage()], $errors);

                return $this->json([
                    'status' => 'uploaded',
                    'ids' => $ids,
                    'errors' => $errors
                ]);
            }

            return $this->redirectToRoute('member_documents', [
                'folderId' => $folder ? $folder->getId() : ''
            ]);
        }

        throw $this->createNotFoundException();
    }

    /**
     * @Route("/documenten/verwijder/{documentId<\d+>}", name="member_documents_delete")
     */
    public function deleteDocument(Request $request, $documentId) {
        $member = $this->getUser();
        if (!$this->isGranted('ROLE_ADMIN'))
            throw $this->createAccessDeniedException('Je kunt dit document niet verwijderen.');

        $em = $this->getDoctrine()->getManager();
        $document = $this->getDoctrine()->getRepository(Document::class)->find($documentId);

        $em->remove($document);
        $em->flush();

        $folder = $document->getFolder();
        return $this->redirectToRoute('member_documents', ['folderId' => $folder ? $folder->getId() : '']);
    }

    /**
     * @Route("/documenten/verwijder-map/{folderId<\d+>}", name="member_documents_delete_folder")
     */
    public function deleteFolder(Request $request, $folderId) {
        $member = $this->getUser();
        if (!$this->isGranted('ROLE_ADMIN'))
            throw $this->createAccessDeniedException('Je kunt deze bestandsmap niet verwijderen.');

        $em = $this->getDoctrine()->getManager();
        $folder = $this->getFolder($folderId);
        if ($folder === null)
            throw $this->createNotFoundException('De opgevraagde bestandsmap is niet beschikbaar.');

        $em->remove($folder);
        $em->flush();

        $parent = $folder->getParent();
        return $this->redirectToRoute('member_documents', ['folderId' => $parent ? $parent->getId() : '']);
    }

    private function getBreadcrumbs(?DocumentFolder $folder) {
        $breadcrumbs = [];
        while ($folder !== null) {
            $breadcrumbs[] = $folder;
            $folder = $folder->getParent();
        }
        array_reverse($breadcrumbs);
        return $breadcrumbs;
    }

    private function getFolder(string $folderId): ?DocumentFolder {
        $folder = null;
        if ($folderId != '') {
            $folder = $this->getDoctrine()->getRepository(DocumentFolder::class)->find($folderId);
            if ($folder === null) {
                throw $this->createNotFoundException('De opgevraagde bestandsmap is niet beschikbaar.');
            }
        }
        return $folder;
    }

}
