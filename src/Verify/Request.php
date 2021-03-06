<?php
declare(strict_types=1);

namespace Nexmo\Verify;

use Nexmo\Entity\Hydrator\ArrayHydrateInterface;

class Request implements ArrayHydrateInterface
{
    const PIN_LENGTH_4 = 4;
    const PIN_LENGTH_6 = 6;

    const WORKFLOW_SMS_TTS_TSS = 1;
    const WORKFLOW_SMS_SMS_TSS = 2;
    const WORKFLOW_TTS_TSS = 3;
    const WORKFLOW_SMS_SMS = 4;
    const WORKFLOW_SMS_TTS = 5;
    const WORKFLOW_SMS = 6;
    const WORKFLOW_TTS = 7;
    
    /**
     * @var string
     */
    protected $number;

    /**
     * @var string
     */
    protected $country;

    /**
     * @var string
     */
    protected $brand;

    /**
     * @var string
     */
    protected $senderId = 'VERIFY';

    /**
     * @var int
     */
    protected $codeLength = 4;

    /**
     * @var string
     */
    protected $locale;

    /**
     * @var int
     */
    protected $pinExpiry = 300;

    /**
     * @var int
     */
    protected $nextEventWait = 300;

    /**
     * @var int
     */
    protected $workflowId = 1;

    public function __construct(string $number, string $brand, int $workflowId = 1)
    {
        $this->number = $number;
        $this->brand = $brand;
        $this->setWorkflowId($workflowId);
    }

    public function getCountry() : ?string
    {
        return $this->country;
    }

    public function setCountry(string $country) : self
    {
        if (strlen($country) !== 2) {
            throw new \InvalidArgumentException('Country must be in two character format');
        }
        $this->country = $country;
        return $this;
    }

    public function getSenderId() : string
    {
        return $this->senderId;
    }

    public function setSenderId(string $senderId) : self
    {
        $this->senderId = $senderId;
        return $this;
    }

    public function getCodeLength() : int
    {
        return $this->codeLength;
    }

    public function setCodeLength(int $codeLength) : self
    {
        if ($codeLength !== 4 || $codeLength !== 6) {
            throw new \InvalidArgumentException('Pin length must be either 4 or 6 digits');
        }

        $this->codeLength = $codeLength;
        return $this;
    }

    public function getLocale() : ?string
    {
        return $this->locale;
    }

    public function setLocale(string $locale) : self
    {
        $this->locale = $locale;
        return $this;
    }

    public function getPinExpiry() : int
    {
        return $this->pinExpiry;
    }

    public function setPinExpiry(int $pinExpiry) : self
    {
        if ($pinExpiry < 60 || $pinExpiry > 3600) {
            throw new \InvalidArgumentException('Pin expiration must be between 60 and 3600 seconds');
        }

        $this->pinExpiry = $pinExpiry;
        return $this;
    }

    public function getNextEventWait() : int
    {
        return $this->nextEventWait;
    }

    public function setNextEventWait(int $nextEventWait) : self
    {
        if ($nextEventWait < 60 || $nextEventWait > 3600) {
            throw new \InvalidArgumentException('Next Event time must be between 60 and 900 seconds');
        }

        $this->nextEventWait = $nextEventWait;
        return $this;
    }

    public function getWorkflowId() : int
    {
        return $this->workflowId;
    }

    public function setWorkflowId(int $workflowId) : self
    {
        if ($workflowId < 1 || $workflowId > 7) {
            throw new \InvalidArgumentException('Workflow ID must be from 1 to 7');
        }

        $this->workflowId = $workflowId;
        return $this;
    }

    public function getNumber() : string
    {
        return $this->number;
    }

    public function getBrand() : string
    {
        return $this->brand;
    }

    public function fromArray(array $data)
    {
        if (array_key_exists('sender_id', $data)) {
            $this->setSenderId($data['sender_id']);
        }

        if (array_key_exists('code_length', $data)) {
            $this->setCodeLength($data['code_length']);
        }

        if (array_key_exists('pin_expiry', $data)) {
            $this->setPinExpiry($data['pin_expiry']);
        }

        if (array_key_exists('next_event_wait', $data)) {
            $this->setNextEventWait($data['next_event_wait']);
        }

        if (array_key_exists('workflow_id', $data)) {
            $this->setWorkflowId($data['workflow_id']);
        }

        if (array_key_exists('country', $data)) {
            $this->setCountry($data['country']);
        }

        if (array_key_exists('lg', $data)) {
            $this->setLocale($data['lg']);
        }
    }

    public function toArray(): array
    {
        $data = [
            'number' => $this->getNumber(),
            'brand' => $this->getBrand(),
            'sender_id' => $this->getSenderId(),
            'code_length' => $this->getCodeLength(),
            'pin_expiry' => $this->getPinExpiry(),
            'next_event_wait' => $this->getNextEventWait(),
            'workflow_id' => $this->getWorkflowId()
        ];

        if ($this->getCountry()) {
            $data['country'] = $this->getCountry();
        }

        if ($this->getLocale()) {
            $data['lg'] = $this->getLocale();
        }

        return $data;
    }
}
