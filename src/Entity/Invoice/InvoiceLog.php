<?php

namespace App\Entity\Invoice;

use App\Entity\User\BaseUser;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use App\Enum\Invoice\InvoiceLogStatus;
use App\Repository\Invoice\InvoiceLogRepository;

#[ORM\Entity(repositoryClass: InvoiceLogRepository::class)]
class InvoiceLog
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'invoiceLogs')]
    private ?Invoice $invoice = null;

    #[ORM\Column(enumType: InvoiceLogStatus::class)]
    private ?InvoiceLogStatus $action = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\ManyToOne(inversedBy: 'invoiceLogs')]
    private ?BaseUser $createdBy = null;

    #[ORM\OneToOne(cascade: ['persist', 'remove'])]
    private ?ClientInvoiceEmailLog $clientInvoiceEmailLog = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $text = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $email = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $ip = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $userAgent = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getInvoice(): ?Invoice
    {
        return $this->invoice;
    }

    public function setInvoice(?Invoice $invoice): self
    {
        $this->invoice = $invoice;

        return $this;
    }

    public function getAction(): ?InvoiceLogStatus
    {
        return $this->action;
    }

    public function setAction(?InvoiceLogStatus $action): self
    {
        $this->action = $action;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): self
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getCreatedBy(): ?BaseUser
    {
        return $this->createdBy;
    }

    public function setCreatedBy(?BaseUser $createdBy): self
    {
        $this->createdBy = $createdBy;

        return $this;
    }

    public function getClientInvoiceEmailLog(): ?ClientInvoiceEmailLog
    {
        return $this->clientInvoiceEmailLog;
    }

    public function setClientInvoiceEmailLog(?ClientInvoiceEmailLog $clientInvoiceEmailLog): self
    {
        $this->clientInvoiceEmailLog = $clientInvoiceEmailLog;

        return $this;
    }

    public function getText(): ?string
    {
        return $this->text;
    }

    public function setText(?string $text): self
    {
        $this->text = $text;

        return $this;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(?string $email): self
    {
        $this->email = $email;

        return $this;
    }

    public function getIp(): ?string
    {
        return $this->ip;
    }

    public function setIp(?string $ip): self
    {
        $this->ip = $ip;

        return $this;
    }

    public function getUserAgent(): ?string
    {
        return $this->userAgent;
    }

    public function setUserAgent(?string $userAgent): self
    {
        $this->userAgent = $userAgent;

        return $this;
    }
}
