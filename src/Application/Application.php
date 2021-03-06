<?php
/**
 * Nexmo Client Library for PHP
 *
 * @copyright Copyright (c) 2016 Nexmo, Inc. (http://nexmo.com)
 * @license   https://github.com/Nexmo/nexmo-php/blob/master/LICENSE.txt MIT License
 */

namespace Nexmo\Application;

use Nexmo\Entity\JsonUnserializableInterface;
use Nexmo\Entity\EntityInterface;
use Nexmo\Entity\Hydrator\ArrayHydrateInterface;
use Nexmo\Entity\JsonResponseTrait;
use Nexmo\Entity\JsonSerializableTrait;
use Nexmo\Entity\Psr7Trait;

class Application implements EntityInterface, \JsonSerializable, JsonUnserializableInterface, ArrayHydrateInterface
{
    use JsonSerializableTrait;
    use Psr7Trait;
    use JsonResponseTrait;

    protected $voiceConfig;
    protected $messagesConfig;
    protected $rtcConfig;
    protected $vbcConfig;

    protected $name;

    protected $keys = [];

    protected $id;

    public function __construct($id = null)
    {
        $this->id = $id;
    }

    public function getId()
    {
        return $this->id;
    }

    public function setVoiceConfig(VoiceConfig $config)
    {
        $this->voiceConfig = $config;
        return $this;
    }

    public function setMessagesConfig(MessagesConfig $config)
    {
        $this->messagesConfig = $config;
        return $this;
    }

    public function setRtcConfig(RtcConfig $config)
    {
        $this->rtcConfig = $config;
        return $this;
    }

    public function setVbcConfig(VbcConfig $config)
    {
        $this->vbcConfig = $config;
        return $this;
    }

    /**
     * @return VoiceConfig
     */
    public function getVoiceConfig()
    {
        if (!isset($this->voiceConfig)) {
            $this->setVoiceConfig(new VoiceConfig());
            $data = @$this->getResponseData();
            if (isset($data['voice']) and isset($data['voice']['webhooks'])) {
                foreach ($data['voice']['webhooks'] as $webhook) {
                    $this->voiceConfig->setWebhook($webhook['endpoint_type'], $webhook['endpoint'], $webhook['http_method']);
                }
            }
        }

        return $this->voiceConfig;
    }

    /**
     * @return MessagesConfig
     */
    public function getMessagesConfig()
    {
        if (!isset($this->messagesConfig)) {
            $this->setMessagesConfig(new MessagesConfig());
            $data = $this->getResponseData();
            if (isset($data['messages']) and isset($data['messages']['webhooks'])) {
                foreach ($data['messages']['webhooks'] as $webhook) {
                    $this->getMessagesConfig()->setWebhook($webhook['endpoint_type'], $webhook['endpoint'], $webhook['http_method']);
                }
            }
        }

        return $this->messagesConfig;
    }

    /**
     * @return RtcConfig
     */
    public function getRtcConfig()
    {
        if (!isset($this->rtcConfig)) {
            $this->setRtcConfig(new RtcConfig());
            $data = $this->getResponseData();
            if (isset($data['rtc']) and isset($data['rtc']['webhooks'])) {
                foreach ($data['rtc']['webhooks'] as $webhook) {
                    $this->getRtcConfig()->setWebhook($webhook['endpoint_type'], $webhook['endpoint'], $webhook['http_method']);
                }
            }
        }

        return $this->rtcConfig;
    }

    /**
     * @return RtcConfig
     */
    public function getVbcConfig() : VbcConfig
    {
        if (!isset($this->vbcConfig)) {
            $this->setVbcConfig(new VbcConfig());
        }

        return $this->vbcConfig;
    }

    public function setPublicKey($key)
    {
        $this->keys['public_key'] = $key;
        return $this;
    }

    public function getPublicKey()
    {
        if (isset($this->keys['public_key'])) {
            return $this->keys['public_key'];
        }
    }

    public function getPrivateKey()
    {
        if (isset($this->keys['private_key'])) {
            return $this->keys['private_key'];
        }
    }

    public function setName($name)
    {
        $this->name = $name;
        return $this;
    }

    public function getName()
    {
        return $this->name;
    }

    public function jsonUnserialize(array $json)
    {
        trigger_error(
            get_class($this) . "::jsonUnserialize is deprecated, please fromArray() instead",
            E_USER_DEPRECATED
        );

        $this->fromArray($json);
    }

    public function jsonSerialize()
    {
        return $this->toArray();
    }

    public function __toString()
    {
        return (string) $this->getId();
    }

    public function fromArray(array $data)
    {
        $this->name = $data['name'];
        $this->id   = $data['id'] ?? null;
        $this->keys = $data['keys'] ?? [];

        if (isset($data['capabilities'])) {
            $capabilities = $data['capabilities'];

            //todo: make voice  hydrate-able
            $this->voiceConfig = new VoiceConfig();
            if (isset($capabilities['voice']) and isset($capabilities['voice']['webhooks'])) {
                foreach ($capabilities['voice']['webhooks'] as $name => $details) {
                    $this->voiceConfig->setWebhook($name, new Webhook($details['address'], $details['http_method']));
                }
            }

            //todo: make messages  hydrate-able
            $this->messagesConfig = new MessagesConfig();
            if (isset($capabilities['messages']) and isset($capabilities['messages']['webhooks'])) {
                foreach ($capabilities['messages']['webhooks'] as $name => $details) {
                    $this->messagesConfig->setWebhook($name, new Webhook($details['address'], $details['http_method']));
                }
            }

            //todo: make rtc  hydrate-able
            $this->rtcConfig = new RtcConfig();
            if (isset($capabilities['rtc']) and isset($capabilities['rtc']['webhooks'])) {
                foreach ($capabilities['rtc']['webhooks'] as $name => $details) {
                    $this->rtcConfig->setWebhook($name, new Webhook($details['address'], $details['http_method']));
                }
            }

            if (isset($capabilities['vbc'])) {
                $this->getVbcConfig()->enable();
            }
        }
    }

    public function toArray(): array
    {
        // Build up capabilities that are set
        $availableCapabilities = [
            'voice' => [VoiceConfig::ANSWER, VoiceConfig::EVENT],
            'messages' => [MessagesConfig::INBOUND, MessagesConfig::STATUS],
            'rtc' => [RtcConfig::EVENT]
        ];

        $capabilities = [];
        foreach ($availableCapabilities as $type => $values) {
            $configAccessorMethod = 'get'.ucfirst($type).'Config';
            foreach ($values as $constant) {
                $webhook = $this->$configAccessorMethod()->getWebhook($constant);
                if ($webhook) {
                    if (!isset($capabilities[$type])) {
                        $capabilities[$type]['webhooks'] = [];
                    }
                    $capabilities[$type]['webhooks'][$constant] = [
                        'address' => $webhook->getUrl(),
                        'http_method' => $webhook->getMethod(),
                    ];
                }
            }
        }

        // Handle VBC specifically
        if ($this->getVbcConfig()->isEnabled()) {
            $capabilities['vbc'] = new \StdClass;
        }

        // Workaround API bug. It expects an object and throws 500
        // if it gets an array
        if (!count($capabilities)) {
            $capabilities = (object) $capabilities;
        }

        return [
            'id' => $this->getId(),
            'name' => $this->getName(),
            'keys' => [
                'public_key' => $this->getPublicKey()
            ],
            'capabilities' => $capabilities
        ];
    }
}
