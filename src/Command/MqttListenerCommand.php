<?php

namespace EnderLab\MqttBundle\Command;

use App\Domotic\Domain\Model\Plugin;
use App\Domotic\Domain\Repository\PluginRepositoryInterface;
#use Doctrine\ORM\EntityManagerInterface;
use EnderLab\MqttBundle\Service\MqttService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Messenger\Exception\ExceptionInterface;

#[AsCommand(
    name: 'marvin:mqtt:listener',
    description: 'MQTT event listener',
)]
class MqttListenerCommand extends Command
{
    public function __construct(
        #private readonly EntityManagerInterface $em,
        private readonly MqttService $mqttService,
        private readonly PluginRepositoryInterface $protocolRepository,
        private readonly ParameterBagInterface $parameters,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption(
                'time-limit',
                null,
                InputOption::VALUE_OPTIONAL,
                'Auto restart after x seconds',
                3600
            )
            ->addOption(
                'memory-limit',
                null,
                InputOption::VALUE_OPTIONAL,
                'Auto restart if memory limit exceeded',
                26214400
            )
        ;
    }

    /**
     * @throws \ReflectionException
     * @throws ExceptionInterface
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $timeLimit = $input->getOption('time-limit');
        $memoryLimit = $input->getOption('memory-limit');
        $baseTopic = $this->parameters->get('mqtt_base_topic');
        $topics = [];

        /*$protocols = $this->protocolRepository->getByType('mqtt');

        /** @var Plugin $protocol */
        /*foreach ($protocols as $protocol) {
            $protocolTopics = $protocol->getMqttTopics();

            /** @var ProtocolMqttTopic $protocolTopic */
            /*foreach ($protocolTopics as $protocolTopic) {
                $topicName = strtr($protocolTopic->getTopic(), ['%base_topic%' => $baseTopic]);
                $topics[$topicName] = [
                    'qos' => $protocolTopic->getQos(),
                    'event' => $protocolTopic->getEventName(),
                ];
            }
        }

        $this->mqttService->start($topics, [
            'time_limit' => $timeLimit,
            'memory_limit' => $memoryLimit
        ]);*/

        return Command::SUCCESS;
    }
}
