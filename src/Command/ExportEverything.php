<?php

namespace App\Command;

use Doctrine\DBAL\Exception;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class ExportEverything extends Command
{
    protected static $defaultName = 'app:export-everything';
    protected static $defaultDescription = 'Fill a directory with csv files that contain every database table.';

    private const NULL_SENTINEL = '\N';

    public function __construct(
        protected ParameterBagInterface $params,
        private readonly EntityManagerInterface $entityManager,
        private readonly UrlGeneratorInterface $urlGenerator,
        ?string $name = null
    ) {
        parent::__construct($name);
    }

    protected function configure()
    {
        $this->addArgument('output-directory', InputArgument::REQUIRED, 'The directory to export to.');
    }

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        if (!$this->params->get('export_key')) {
            $output->writeln('Export key not set, cannot export.');
            return Command::FAILURE;
        }

        $outputDir = $input->getArgument('output-directory');

        // Check if the output directory exists and is writable
        if (!is_dir($outputDir)) {
            $output->writeln(sprintf('Creating output directory: %s', $outputDir));
            if (!mkdir($outputDir, 0755, true)) {
                $output->writeln(sprintf('<error>Could not create output directory: %s</error>', $outputDir));
                return Command::FAILURE;
            }
        }

        if (!is_writable($outputDir)) {
            $output->writeln(sprintf('<error>Output directory is not writable: %s</error>', $outputDir));
            return Command::FAILURE;
        }

        try {
            // Get the database connection
            $connection = $this->entityManager->getConnection();

            // Get all tables in the database
            $tables = $connection->createSchemaManager()->listTableNames();

            // Skip the sessions table and the migrations bookkeeping table
            // (named admin_migration_versions in doctrine_migrations.yaml)
            $tables = array_filter($tables, function($table) {
                return $table !== 'sessions' && !str_ends_with($table, 'migration_versions');
            });

            // Ensure division_member table is included (it might be missing as it's a join table)
            if (!in_array('division_member', $tables)) {
                $tables[] = 'division_member';
                $output->writeln('Added division_member table to export list');
            }

            $output->writeln(sprintf('Found %d tables to export', count($tables)));

            $failedTables = [];

            // Export each table to a CSV file
            foreach ($tables as $table) {
                $output->writeln(sprintf('Exporting table: %s', $table));

                try {
                    // Get the table columns to handle empty tables and to ensure consistent column order
                    $columns = $connection->createSchemaManager()->listTableColumns($table);
                    $headers = array_map(fn($column) => $column->getName(), $columns);

                    // Add api_download_url column to admin_document table
                    if ($table === 'admin_document') {
                        $headers[] = 'api_download_url';
                    }

                    // Count the rows in the table
                    $countStmt = $connection->prepare("SELECT COUNT(*) as count FROM `$table`");
                    $countResult = $countStmt->executeQuery();
                    $count = $countResult->fetchAssociative()['count'];

                    if ($count == 0) {
                        $output->writeln(sprintf('Table %s is empty, creating empty CSV file', $table));
                        // Create an empty CSV file with headers
                        $this->exportToCsv($outputDir, $table, $headers, []);
                        continue;
                    }

                    $output->writeln(sprintf('Table %s has %d rows', $table, $count));

                    // For large tables, process in batches to avoid memory issues
                    $batchSize = 10000;
                    $offset = 0;
                    $firstBatch = true;

                    while ($offset < $count) {
                        $output->writeln(sprintf('Processing batch %d to %d of table %s', $offset, min($offset + $batchSize, $count), $table));

                        // Get a batch of data from the table
                        $stmt = $connection->prepare("SELECT * FROM `$table` LIMIT $batchSize OFFSET $offset");
                        $result = $stmt->executeQuery();
                        $data = $result->fetchAllAssociative();

                        if (empty($data)) {
                            break;
                        }

                        // For the first batch, create a new file, for subsequent batches append to the existing file
                        $this->exportToCsv($outputDir, $table, $headers, $data, !$firstBatch);

                        $firstBatch = false;
                        $offset += $batchSize;
                    }

                    $output->writeln(sprintf('Successfully exported %d rows from table %s', $count, $table));
                } catch (\Exception $e) {
                    $output->writeln(sprintf('<error>Error exporting table %s: %s</error>', $table, $e->getMessage()));
                    $failedTables[] = $table;
                }
            }

            if ($failedTables !== []) {
                $output->writeln(sprintf('<error>Export failed for %d table(s): %s</error>', count($failedTables), implode(', ', $failedTables)));
                return Command::FAILURE;
            }

            $output->writeln('Export completed successfully');
            return Command::SUCCESS;
        } catch (Exception $e) {
            $output->writeln(sprintf('<error>Database error: %s</error>', $e->getMessage()));
            return Command::FAILURE;
        } catch (\Exception $e) {
            $output->writeln(sprintf('<error>Error: %s</error>', $e->getMessage()));
            return Command::FAILURE;
        }
    }

    /**
     * Export data to a CSV file
     *
     * @param string $outputDir The output directory
     * @param string $table The table name
     * @param array $headers The column headers
     * @param array $data The data to export
     * @param bool $append Whether to append to an existing file
     */
    private function exportToCsv(string $outputDir, string $table, array $headers, array $data, bool $append = false): void
    {
        $filename = sprintf('%s/%s.csv', $outputDir, $table);
        $mode = $append ? 'a' : 'w';
        $file = fopen($filename, $mode);
        if ($file === false) {
            throw new \RuntimeException(sprintf('Could not open file for writing: %s', $filename));
        }

        if (!$append) {
            // Write the headers
            fputcsv($file, $headers, ',', '"', '');
        }

        // Write the data
        foreach ($data as $row) {
            // Ensure all columns are present in the correct order
            $orderedRow = [];
            foreach ($headers as $header) {
                if ($table === 'admin_document' && $header === 'api_download_url') {
                    // Generate API download URL for documents
                    $documentId = $row['id'] ?? null;
                    if ($documentId) {
                        $orderedRow[] = $this->urlGenerator->generate('api_download', ['documentId' => $documentId], UrlGeneratorInterface::ABSOLUTE_URL);
                    } else {
                        $orderedRow[] = null;
                    }
                } else {
                    $orderedRow[] = $row[$header] ?? null;
                }
            }
            // Empty escape parameter: strict RFC 4180 output (backslashes are
            // literal), and keeps the \N NULL sentinel unquoted.
            fputcsv($file, array_map(
                fn($value) => $this->encodeNull($table, $value),
                $orderedRow
            ), ',', '"', '');
        }

        fclose($file);
    }

    /**
     * Encode NULL as the \N sentinel (mysqldump convention) so importers can
     * distinguish NULL from an empty string. Real values are passed through
     * unchanged; a real value equal to the sentinel would make the export
     * ambiguous, so that fails the export instead.
     */
    private function encodeNull(string $table, $value)
    {
        if ($value === null) {
            return self::NULL_SENTINEL;
        }
        if ($value === self::NULL_SENTINEL) {
            throw new \RuntimeException(sprintf('Table %s contains a value equal to the NULL sentinel "%s", refusing to write an ambiguous export.', $table, self::NULL_SENTINEL));
        }
        return $value;
    }
}
