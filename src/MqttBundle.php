<?php

namespace EnderLab\MqttBundle;

use App\Domotic\Infrastructure\Attribute\AsMqttListener;
use Symfony\Component\DependencyInjection\ChildDefinition;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\HttpKernel\Bundle\AbstractBundle;

class MqttBundle extends AbstractBundle
{
    public function loadExtension(array $config, ContainerConfigurator $container, ContainerBuilder $builder): void
    {
        $container->import('../config/services.yaml');

        $builder->registerAttributeForAutoconfiguration(AsMqttListener::class, static function (ChildDefinition $definition, AsMqttListener $attribute): void {
            $definition
                ->addTag('mqtt.event.message', ['event' => $attribute->event, 'priority' => $attribute->priority])
                ->setLazy(true)
            ;
        });
    }
}
