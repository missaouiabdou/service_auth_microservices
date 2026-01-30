<?php

declare(strict_types=1);

namespace App\Application\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:generate-jwt-keys',
    description: 'Generate RSA key pair for JWT authentication'
)]
final class GenerateJwtKeysCommand extends Command
{
    protected function configure(): void
    {
        $this
            ->addOption('passphrase', 'p', InputOption::VALUE_OPTIONAL, 'Passphrase for the private key', '')
            ->addOption('force', 'f', InputOption::VALUE_NONE, 'Force overwrite existing keys');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $projectDir = dirname(__DIR__, 3);
        $jwtDir = $projectDir . '/var/jwt';
        $privateKeyPath = $jwtDir . '/private.pem';
        $publicKeyPath = $jwtDir . '/public.pem';

        // Create jwt directory if it doesn't exist
        if (!is_dir($jwtDir)) {
            mkdir($jwtDir, 0755, true);
        }

        // Check if keys already exist
        if (file_exists($privateKeyPath) && !$input->getOption('force')) {
            $io->warning('JWT keys already exist. Use --force to overwrite.');
            return Command::FAILURE;
        }

        $passphrase = $input->getOption('passphrase');

        $io->title('Generating RSA Key Pair for JWT');

        try {
            // Generate private key
            $config = [
                'digest_alg' => 'sha256',
                'private_key_bits' => 4096,
                'private_key_type' => OPENSSL_KEYTYPE_RSA,
            ];

            $privateKey = openssl_pkey_new($config);
            if ($privateKey === false) {
                throw new \RuntimeException('Failed to generate private key');
            }

            // Export private key
            openssl_pkey_export($privateKey, $privateKeyPem, $passphrase);
            file_put_contents($privateKeyPath, $privateKeyPem);
            chmod($privateKeyPath, 0600);

            // Export public key
            $publicKeyDetails = openssl_pkey_get_details($privateKey);
            if ($publicKeyDetails === false) {
                throw new \RuntimeException('Failed to extract public key');
            }

            file_put_contents($publicKeyPath, $publicKeyDetails['key']);
            chmod($publicKeyPath, 0644);

            $io->success([
                'JWT keys generated successfully!',
                'Private key: ' . $privateKeyPath,
                'Public key: ' . $publicKeyPath,
            ]);

            if (!empty($passphrase)) {
                $io->note('Don\'t forget to set JWT_PASSPHRASE in your .env.local file');
            }

            return Command::SUCCESS;
        } catch (\Throwable $e) {
            $io->error('Failed to generate JWT keys: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }
}