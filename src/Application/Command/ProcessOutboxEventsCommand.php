<?php

declare(strict_types=1);

namespace App\Application\Command;

use App\Application\Service\OutboxProcessor;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:process-outbox-events',
    description: 'Process pending outbox events and publish to message broker'
)]
final class ProcessOutboxEventsCommand extends Command
{
    public function __construct(
        private readonly OutboxProcessor $outboxProcessor,
        private readonly LoggerInterface $logger
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $io->title('Processing Outbox Events');

        try {
            $this->outboxProcessor->processEvents();
            $io->success('Outbox events processed successfully');
            return Command::SUCCESS;
        } catch (\Throwable $e) {
            $this->logger->error('Failed to process outbox events', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            $io->error('Failed to process outbox events: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }
}