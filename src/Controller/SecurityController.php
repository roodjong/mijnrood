<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use App\Entity\Member;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mime\Address;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;


class SecurityController extends AbstractController
{
    /** @Route("/logout", name="logout") */
    public function logout() {

    }

    /**
     * @Route("/login", name="login")
     */
    public function login(AuthenticationUtils $authenticationUtils): Response
    {
        $error = $authenticationUtils->getLastAuthenticationError();
        $lastUsername = $authenticationUtils->getLastUsername();

        return $this->render('user/security/login.html.twig', [
            // parameters usually defined in Symfony login forms
            'error' => $error,
            'last_username' => $lastUsername,

            // the string used to generate the CSRF token. If you don't define
            // this parameter, the login form won't include a CSRF token
            'csrf_token_intention' => 'authenticate',

            // the URL users are redirected to after the login (default: '/admin')
            'target_path' => $this->generateUrl('admin_dashboard'),
        ]);
    }

    /** @Route("/wachtwoord-opvragen", name="request_new_password") */
    public function requestNewPassword(Request $request, AuthenticationUtils $authenticationUtils, MailerInterface $mailer): Response {
        $lastUsername = $authenticationUtils->getLastUsername();

        $form = $this->createFormBuilder()
            ->add('username', null, ['label' => 'Lidnummer of e-mailadres'])
            ->getForm([
                'username' => $lastUsername
            ]);
        $orgName = $this->getParameter('app.organizationName');
        $noreply = $this->getParameter('app.noReplyAddress');
        $form->handleRequest($request);
        $success = false;
        if ($form->isSubmitted() && $form->isValid()) {
            $repo = $this->getDoctrine()->getRepository(Member::class);
            $member = $repo->createQueryBuilder('m')
                ->where('m.id = ?1 OR m.email = ?1')
                ->setParameter(1, $form['username']->getData())
                ->setMaxResults(1)
                ->getQuery()
                ->getOneOrNullResult()
            ;

            if ($member !== null) {
                $member->setNewPasswordToken(hash("sha256", $member->getEmail() . random_bytes(64)));
                $this->getDoctrine()->getManager()->flush();

                $message = (new Email())
                    ->subject('Aanvraag nieuw wachtwoord')
                    ->to(new Address($member->getEmail(), $member->getFirstName() .' '. $member->getLastName()))
                    ->from(new Address($noreply, $orgName))
                    ->html(
                        $this->renderView('email/html/request_new_password.html.twig', ['member' => $member])
                    )
                    ->text(
                        $this->renderView('email/text/request_new_password.txt.twig', ['member' => $member])
                    );
                $mailer->send($message);
            }

            $success = true;
        }

        return $this->render('user/security/request_new_password.html.twig', [
            'form' => $form->createView(),
            'success' => $success
        ]);
    }

    /**
     * @Route("/wachtwoord-instellen/{token}", name="set_new_password")
     */
    public function setNewPassword(Request $request, UserPasswordEncoderInterface $encoder, $token) {
        $em = $this->getDoctrine()->getManager();
        $repo  = $em->getRepository(Member::class);
        $member = $repo->findOneByNewPasswordToken($token);

        $form = null;
        $error = null;
        $errorLink = null;
        $success = false;
        $hasPassword = false;
        if ($member === null || ((time() - $member->getNewPasswordTokenGeneratedTime()->format('U')) > (3600 * 12) && $member->hasPassword())) {
            $error = 'Deze link is verlopen.';
            $errorLink = ['Vraag opnieuw een link aan.', $this->generateUrl('request_new_password')];
        } else {
            $hasPassword = $member->hasPassword();

            $form = $this->createFormBuilder()
            ->add('password', RepeatedType::class, [
                'type' => PasswordType::class,
                'invalid_message' => 'De wachtwoorden moeten overeenkomen.',
                'required' => true,
                'first_options'  => ['label' => 'Nieuw wachtwoord', 'attr' => ['placeholder' => 'Nieuw wachtwoord']],
                'second_options' => ['label' => 'Nieuw wachtwoord (herhaal)', 'attr' => ['placeholder' => 'Nieuw wachtwoord (herhaal)']],
                'error_bubbling' => true
            ])
            ->getForm();

            $success = false;
            $form->handleRequest($request);
            if ($form->isSubmitted() && $form->isValid()) {
                $newPasswordHash = $encoder->encodePassword($member, $form['password']->getData());
                $member->setPasswordHash($newPasswordHash);
                $member->setNewPasswordToken(null);
                $em->flush();
                $success = true;
            } else {
                $errors = [];
                foreach ($form->getErrors() as $error)
                    $errors[] = $error->getMessage();
                $error = implode(' ', $errors);
            }
        }


        return $this->render('user/security/set_new_password.html.twig', [
            'form' => $form === null ? null : $form->createView(),
            'member' => $member,
            'success' => $success,
            'hasPassword' => $hasPassword,
            'error' => $error,
            'errorLink' => $errorLink
        ]);
    }

}
