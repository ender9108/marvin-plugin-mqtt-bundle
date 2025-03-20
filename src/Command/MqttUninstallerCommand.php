<?php

namespace EnderLab\MqttBundle\Command;

use App\System\Infrastructure\Symfony\Command\AbstractPluginManagerCommand;
use Exception;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'zigbee2mqtt:uninstaller',
    description: 'Uninstall zigbee2mqtt bundle',
)]
class MqttUninstallerCommand extends AbstractPluginManagerCommand
{
    protected function configure(): void
    {
    }

    /**
     * @throws Exception
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $reference = $this->parameters->get('mqtt_reference');
        $this->startUninstall(function () {
            //
        }, $reference, $input, $output);

        return Command::SUCCESS;
    }


}
