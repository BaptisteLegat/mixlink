<?php

namespace App\Command;

use App\Entity\Session;
use App\Repository\SessionRepository;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Override;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:session:cleanup',
    description: 'Clean up old inactive sessions',
)]
class SessionCleanupCommand extends Command
{
    public function __construct(
        private SessionRepository $sessionRepository,
        private EntityManagerInterface $entityManager,
        private LoggerInterface $logger,
    ) {
        parent::__construct();
    }

    #[Override]
    protected function configure(): void
    {
        $this
            ->addArgument('days', InputArgument::OPTIONAL, 'Number of days after which inactive sessions should be deleted', 7)
            ->setHelp('This command deletes inactive sessions that have been ended for more than the specified number of days.')
        ;
    }

    #[Override]
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $days = (int) $input->getArgument('days');

        if ($days < 1) {
            $io->error('The number of days must be at least 1.');

            return Command::FAILURE;
        }

        $cutoffDate = (new DateTimeImmutable())->modify("-{$days} days");

        $io->info("Cleaning up inactive sessions older than {$days} days (before {$cutoffDate->format('Y-m-d H:i:s')})");

        try {
            $oldSessions = $this->sessionRepository->findOldInactiveSessions($cutoffDate);

            $count = count($oldSessions);

            if (0 === $count) {
                $io->success('No old inactive sessions found to clean up.');

                return Command::SUCCESS;
            }

            $io->progressStart($count);

            /** @var Session $session */
            foreach ($oldSessions as $session) {
                $this->logger->info('Deleting old inactive session', [
                    'sessionId' => $session->getId()?->toRfc4122(),
                    'sessionCode' => $session->getCode(),
                    'endedAt' => $session->getEndedAt()?->format('Y-m-d H:i:s'),
                ]);

                $this->entityManager->remove($session);
                $io->progressAdvance();
            }

            $this->entityManager->flush();
            $io->progressFinish();

            $io->success("Successfully deleted {$count} old inactive sessions.");

            return Command::SUCCESS;
        } catch (Exception $e) {
            $this->logger->error('Error during session cleanup', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            $io->error('An error occurred during cleanup: '.$e->getMessage());

            return Command::FAILURE;
        }
    }
}
