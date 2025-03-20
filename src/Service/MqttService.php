<?php

namespace EnderLab\MqttBundle\Service;

#use App\Domotic\Infrastructure\Attribute\AsMqttListener;
#use EnderLab\Zigbee2mqttBundle\Messenger\ZigbeeMqttEvent;
use Exception;
use ReflectionAttribute;
use ReflectionClass;
use ReflectionException;
use Simps\MQTT\Client;
use Simps\MQTT\Config\ClientConfig;
use Simps\MQTT\Hex\ReasonCode;
use Simps\MQTT\Protocol\Types;
use Symfony\Component\DependencyInjection\Attribute\AutowireIterator;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Swoole\Coroutine;
use Symfony\Component\Messenger\Exception\ExceptionInterface;
use Symfony\Component\Messenger\MessageBusInterface;
use function Swoole\Coroutine;

class MqttService
{
    private ?Client $connection = null;

    private array $config = [
        'userName' => null,
        'password' => null,
        'clientId' => null,
        'keepAlive' => 10,
        'protocolName' => 'MQTT',
        'protocolLevel' => 5,
        'properties' => [],
        'delay' => 3000,
        'maxAttempts' => 5,
        'swooleConfig' => [
            'open_mqtt_protocol' => true,
            'package_max_length' => 2 * 1024 * 1024,
            'connect_timeout' => 5.0,
            'write_timeout' => 5.0,
            'read_timeout' => 5.0,
        ],
        'time_limit' => 3600,
        'memory_limit' => 26214400
    ];

    private ?Client $client = null;

    private bool $interrupted = false;

    private array $topicEvents = [];

    public function __construct(
        private readonly ParameterBagInterface $parameters,
        private readonly MessageBusInterface $messageBus,
        #[AutowireIterator('mqtt.event.message')]
        private readonly iterable $eventHandlers,
    ) {
        $this->updateConfig();
    }

    /**
     * @throws ReflectionException
     * @throws ExceptionInterface
     */
    public function start(array $topics, array $options = []): void
    {
        $this->updateConfig($options);
        $this->initEventHandler($topics);

        Coroutine\run(/** @throws Exception
         * @throws ExceptionInterface
         */ function () use ($topics) {
            $this->client = new Client(
                $this->parameters->get('mqtt_host'),
                $this->parameters->get('mqtt_port'),
                $this->getConnectionConfig(),
                Client::SYNC_CLIENT_TYPE
            );

            $this->client->connect(true);
            $this->client->subscribe($topics);
            $loopStartedAt = microtime(true);

            while (true) {
                if ($this->interrupted) {
                    $this->interrupted = false;
                    break;
                }

                $message = $this->readBuffer();

                if (null !== $message) {
                    if (isset($this->topicEvents[$message['topic']])) {
                        foreach ($this->topicEvents[$message['topic']] as $events) {
                            foreach ($events as $event) {
                                dump($message['topic']);
                                $event->topic = $message['topic'];
                                $event->payload = $message['message'];

                                $this->messageBus->dispatch($event);
                            }
                        }
                    }
                }

                if (
                    $this->config['time_limit'] !== null &&
                    (microtime(true) - $loopStartedAt) > $this->config['time_limit']
                ) {
                    dump('Restart after time limit reached');
                    $this->interrupt();
                }
            }
        });
    }

    public function interrupt(): void
    {
        $this->interrupted = true;
    }

    /**
     * @throws Exception
     */
    private function readBuffer(): ?array
    {
        $buffer = $this->client->recv();

        if ($buffer && $buffer !== true) {
            if ($buffer['type'] === Types::PUBLISH) {
                if ($buffer['qos'] === 1) {
                    $this->client->send(
                        [
                            'type' => Types::PUBACK,
                            'message_id' => $buffer['message_id'],
                        ],
                        false
                    );
                }

                if (isset($buffer['message'])) {
                    $buffer['message'] = json_decode($buffer['message'], true);
                }

                return $buffer;
            }

            if ($buffer['type'] === Types::DISCONNECT) {
                $this->client->close($buffer['code']);

                throw new Exception(printf(
                    "Broker is disconnected, The reason is %s [%d]\n",
                    ReasonCode::getReasonPhrase($buffer['code']),
                    $buffer['code']
                ));
            }
        }

        return null;
    }

    private function getConnectionConfig(): ClientConfig
    {
        return new ClientConfig($this->config);
    }

    private function updateConfig(array $config = []): void
    {
        $this->config = array_merge($this->config, [
            'userName' => $this->parameters->get('mqtt_user'),
            'password' => $this->parameters->get('mqtt_password'),
            'clientId' => $this->parameters->get('mqtt_client_id'),
            'keepAlive' => 10,
            'protocolName' => 'MQTT',
            'protocolLevel' => (int) $this->parameters->get('mqtt_version'),
        ]);

        foreach ($config as $key => $value) {
            if (array_key_exists($key, $this->config)) {
                $this->config[$key] = $value;
            }
        }
    }

    /**
     * @throws ReflectionException
     */
    private function initEventHandler(array $topics): void
    {
        foreach ($this->eventHandlers as $eventHandler) {
            /*$reflection = new ReflectionClass($eventHandler);
            $attributes = $reflection->getAttributes(AsMqttListener::class);

            /** @var ReflectionAttribute $attribute */
            /*foreach ($attributes as $attribute) {
                /** @var AsMqttListener $instance */
                /*$instance = $attribute->newInstance();
                $event = $instance->event;
                $priority = $instance->priority;

                foreach ($topics as $topic => $values) {
                    if (!isset($this->topicEvents[$topic])) {
                        $this->topicEvents[$topic] = [];
                    }

                    if ((string) $values['event'] === $event) {
                        $this->topicEvents[$topic][$priority][] = $eventHandler;
                    }

                    sort($this->topicEvents[$topic], SORT_NUMERIC);
                }
            }*/
        }
    }
}
