<?php

namespace App\Entity\Invoice;

use App\Entity\Client\ClientAddress;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use App\Enum\Invoice\InvoiceEmailStatus;
use App\Repository\Invoice\ClientInvoiceEmailLogRepository;
use App\Entity\User\BaseUser;

#[ORM\Entity(repositoryClass: ClientInvoiceEmailLogRepository::class)]
class ClientInvoiceEmailLog
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'invoiceEmailLogs')]
    private ?Invoice $invoice = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\ManyToOne(inversedBy: 'invoiceEmailLogsCreatedBy')]
    private ?BaseUser $createdBy = null;

    #[ORM\ManyToOne(inversedBy: 'invoiceEmailLogs')]
    private ?ClientAddress $clientAddress = null;

    #[ORM\Column(enumType: InvoiceEmailStatus::class)]
    private ?InvoiceEmailStatus $status = null;

    #[ORM\Column(length: 255)]
    private ?string $emailSubject = null;

    #[ORM\Column(type: Types::TEXT)]
    private ?string $emailContent = null;

    #[ORM\Column(length: 255)]
    private ?string $hash = null;

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

    public function getClientAddress(): ?ClientAddress
    {
        return $this->clientAddress;
    }

    public function setClientAddress(?ClientAddress $clientAddress): self
    {
        $this->clientAddress = $clientAddress;

        return $this;
    }

    public function getStatus(): ?InvoiceEmailStatus
    {
        return $this->status;
    }

    public function setStatus(?InvoiceEmailStatus $status): self
    {
        $this->status = $status;

        return $this;
    }

    public function getEmailSubject(): ?string
    {
        return $this->emailSubject;
    }

    public function setEmailSubject(string $emailSubject): self
    {
        $this->emailSubject = $emailSubject;

        return $this;
    }

    public function getEmailContent(): ?string
    {
        return $this->emailContent;
    }

    public function setEmailContent(string $emailContent): self
    {
        $this->emailContent = $emailContent;

        return $this;
    }

    public function getHash(): ?string
    {
        return $this->hash;
    }

    public function setHash(string $hash): self
    {
        $this->hash = $hash;

        return $this;
    }
}
