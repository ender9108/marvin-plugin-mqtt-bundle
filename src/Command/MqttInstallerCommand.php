<?php

namespace EnderLab\MqttBundle\Command;

use App\System\Infrastructure\Symfony\Command\AbstractPluginManagerCommand;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Exception;

#[AsCommand(
    name: 'zigbee2mqtt:installer',
    description: 'Install zigbee2mqtt bundle',
)]
class MqttInstallerCommand extends AbstractPluginManagerCommand
{
    protected function configure(): void
    {
    }

    /**
     * @throws Exception
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->info('Start zigbee2mqtt installation');

        $this->startInstallation(function() {

        }, 'zigbee2mqtt', $input, $output);

        $io->success('Zigbee2mqtt is now installed');
        return Command::SUCCESS;
    }


}
