<?php

namespace App\Entity\Invoice;

use App\Entity\BankAccount\BankAccount;
use App\Entity\User\BaseUser;
use App\Enum\Invoice\InvoiceDdsOptions;
use App\Enum\Invoice\InvoicePaymentMethod;
use App\Enum\Invoice\InvoicePromotionType;
use App\Enum\Invoice\InvoiceType;
use App\Enum\Invoice\InvoicedStatus;
use App\Repository\Invoice\InvoiceRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Symfony\Component\Validator\Constraints as Assert;
use App\Entity\Client\Client;

#[ORM\Entity(repositoryClass: InvoiceRepository::class)]
// #[UniqueEntity(fields: ['number'])]
// #[ORM\UniqueConstraint(columns: ['number'])]


#[ORM\Table(name: 'invoice', uniqueConstraints: [
    new ORM\UniqueConstraint(name: 'uniq_invoice_type_number', columns: ['type','number'])
])]
#[UniqueEntity(fields: ['type', 'number'], errorPath: 'number', message: 'This number is already used for this document type.')]
class Invoice
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 0)]
    private ?string $number = null;

    #[ORM\ManyToOne]
    private ?Client $client = null;

    #[ORM\Column(type: Types::DATE_IMMUTABLE)]
    private ?\DateTimeImmutable $issueDate = null;

    #[ORM\Column(type: Types::DATE_IMMUTABLE)]
    private ?\DateTimeImmutable $taxDate = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\ManyToOne]
    private ?BaseUser $createdBy = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    private ?string $subTotalPrice = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    private ?string $promotionPrice = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    private ?string $taxBasePrice = null;

    #[ORM\Column]
    private ?int $ddsPercentage = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    private ?string $totalPrice = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $note = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $noteEng = null;

    #[ORM\Column(enumType: InvoicePaymentMethod::class)]
    private ?InvoicePaymentMethod $paymentMethod = null;

    #[ORM\ManyToOne]
    private ?BaseUser $publisher = null;

    /**
     * @var Collection<int, InvoiceItem>|InvoiceItem[]
     */
    #[ORM\OneToMany(mappedBy: 'invoice', targetEntity: InvoiceItem::class, cascade:["persist"])]
    private Collection $invoiceItems;

    #[ORM\Column(enumType: InvoiceDdsOptions::class)]
    private ?InvoiceDdsOptions $ddsOption = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    private ?string $ddsPrice = null;


    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    private ?string $promotionValue = null;

    #[ORM\Column(enumType: InvoicePromotionType::class)]
    private ?InvoicePromotionType $promotionType = null;

    #[ORM\Column(enumType: InvoiceType::class)]
    private ?InvoiceType $type = null;

    #[ORM\ManyToOne(targetEntity: self::class, inversedBy: 'invoices')]
    private ?self $parent = null;

    #[ORM\OneToMany(mappedBy: 'parent', targetEntity: self::class)]
    private Collection $invoices;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $name = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $vat = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $eek = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $address = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $responsiblePerson = null;

    #[ORM\Column(length: 2, nullable: true)]
    private ?string $countryCode = null;

    #[ORM\ManyToOne(inversedBy: 'invoices')]
    private ?BankAccount $bankAccount = null;

    #[ORM\Column(enumType: InvoicedStatus::class, options: ['default' => 0])]
    private ?InvoicedStatus $isPaid = null;

    #[ORM\Column(enumType: InvoicedStatus::class, options: ['default' => 0])]
    private ?InvoicedStatus $isPosted = null;

    /**
     * @var Collection<int, InvoicePayment>
     */
    #[ORM\OneToMany(targetEntity: InvoicePayment::class, mappedBy: 'invoice')]
    private Collection $invoicePayments;

    public function __construct()
    {
        $this->invoiceItems = new ArrayCollection();
        $this->invoices = new ArrayCollection();
        $this->invoicePayments = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNumber(): ?string
    {
        $number = $this->number;
        if(!empty($number)) {
            $number = str_pad($number, 10, '0', STR_PAD_LEFT);
        }
        return $number;
    }

    public function setNumber(string $number): self
    {
        $this->number = $number;

        return $this;
    }

    public function getClient(): ?Client
    {
        return $this->client;
    }

    public function setClient(?Client $client): self
    {
        $this->client = $client;

        return $this;
    }

    public function getIssueDate(): ?\DateTimeImmutable
    {
        return $this->issueDate;
    }

    public function setIssueDate(\DateTimeImmutable $issueDate): self
    {
        $this->issueDate = $issueDate;

        return $this;
    }

    public function getTaxDate(): ?\DateTimeImmutable
    {
        return $this->taxDate;
    }

    public function setTaxDate(\DateTimeImmutable $taxDate): self
    {
        $this->taxDate = $taxDate;

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


    public function getSubTotalPrice(): ?string
    {
        return $this->subTotalPrice;
    }

    public function setSubTotalPrice(string $subTotalPrice): self
    {
        $this->subTotalPrice = $subTotalPrice;

        return $this;
    }

    public function getPromotionPrice(): ?string
    {
        return $this->promotionPrice;
    }

    public function setPromotionPrice(string $promotionPrice): self
    {
        $this->promotionPrice = $promotionPrice;

        return $this;
    }

    public function getTaxBasePrice(): ?string
    {
        return $this->taxBasePrice;
    }

    public function setTaxBasePrice(string $taxBasePrice): self
    {
        $this->taxBasePrice = $taxBasePrice;

        return $this;
    }

    public function getDdsPercentage(): ?int
    {
        return $this->ddsPercentage;
    }

    public function setDdsPercentage(int $ddsPercentage): self
    {
        $this->ddsPercentage = $ddsPercentage;

        return $this;
    }

    public function getTotalPrice(): ?string
    {
        return $this->totalPrice;
    }

    public function setTotalPrice(string $totalPrice): self
    {
        $this->totalPrice = $totalPrice;

        return $this;
    }

    public function getNote(): ?string
    {
        return $this->note;
    }

    public function setNote(?string $note): self
    {
        $this->note = $note;

        return $this;
    }

    public function getPaymentMethod(): ?InvoicePaymentMethod
    {
        return $this->paymentMethod;
    }

    public function setPaymentMethod(InvoicePaymentMethod $paymentMethod): self
    {
        $this->paymentMethod = $paymentMethod;

        return $this;
    }

    public function getPublisher(): ?BaseUser
    {
        return $this->publisher;
    }

    public function setPublisher(?BaseUser $publisher): self
    {
        $this->publisher = $publisher;

        return $this;
    }

    /**
     * @return Collection<int, InvoiceItem>
     */
    public function getInvoiceItems(): Collection
    {
        return $this->invoiceItems;
    }

    public function addInvoiceItem(InvoiceItem $invoiceItem): self
    {
        if (!$this->invoiceItems->contains($invoiceItem)) {
            $this->invoiceItems->add($invoiceItem);
            $invoiceItem->setInvoice($this);
        }

        return $this;
    }

    public function removeInvoiceItem(InvoiceItem $invoiceItem): self
    {
        if ($this->invoiceItems->removeElement($invoiceItem)) {
            // set the owning side to null (unless already changed)
            if ($invoiceItem->getInvoice() === $this) {
                $invoiceItem->setInvoice(null);
            }
        }

        return $this;
    }

    public function getDdsOption(): ?InvoiceDdsOptions
    {
        return $this->ddsOption;
    }

    public function setDdsOption(InvoiceDdsOptions $ddsOption): self
    {
        $this->ddsOption = $ddsOption;

        return $this;
    }

    public function getDdsPrice(): ?string
    {
        return $this->ddsPrice;
    }

    public function setDdsPrice(string $ddsPrice): self
    {
        $this->ddsPrice = $ddsPrice;

        return $this;
    }

    public function getPromotionValue(): ?string
    {
        return $this->promotionValue;
    }

    public function setPromotionValue(string $promotionValue): self
    {
        $this->promotionValue = $promotionValue;

        return $this;
    }

    public function getPromotionType(): ?InvoicePromotionType
    {
        return $this->promotionType;
    }

    public function setPromotionType(InvoicePromotionType $promotionType): self
    {
        $this->promotionType = $promotionType;

        return $this;
    }

    #[Assert\Callback]
    public function validate(ExecutionContextInterface $context, mixed $payload): void
    {
        if($this->getDdsOption() === InvoiceDdsOptions::WithDds && $this->getDdsPercentage() == 0) {
            $context->buildViolation('ДДС трябва да е > 0')
                ->atPath('ddsPercentage')
                ->addViolation();
        }

        if($this->getDdsOption() !== InvoiceDdsOptions::WithDds && $this->getDdsPercentage() > 0) {
            $context->buildViolation('ДДС трябва да е = 0')
                ->atPath('ddsPercentage')
                ->addViolation();
        }
    }

    public function getType(): ?InvoiceType
    {
        return $this->type;
    }

    public function setType(InvoiceType $type): self
    {
        $this->type = $type;

        return $this;
    }

    public function getParent(): ?self
    {
        return $this->parent;
    }

    public function setParent(?self $parent): self
    {
        $this->parent = $parent;

        return $this;
    }

    /**
     * @return Collection<int, self>
     */
    public function getInvoices(): Collection
    {
        return $this->invoices;
    }

    public function addInvoice(self $invoice): self
    {
        if (!$this->invoices->contains($invoice)) {
            $this->invoices->add($invoice);
            $invoice->setParent($this);
        }

        return $this;
    }

    public function removeInvoice(self $invoice): self
    {
        if ($this->invoices->removeElement($invoice)) {
            // set the owning side to null (unless already changed)
            if ($invoice->getParent() === $this) {
                $invoice->setParent(null);
            }
        }

        return $this;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(?string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getVat(): ?string
    {
        return $this->vat;
    }

    public function setVat(?string $vat): self
    {
        $this->vat = $vat;

        return $this;
    }

    public function getEek(): ?string
    {
        return $this->eek;
    }

    public function setEek(?string $eek): self
    {
        $this->eek = $eek;

        return $this;
    }

    public function getAddress(): ?string
    {
        return $this->address;
    }

    public function setAddress(?string $address): self
    {
        $this->address = $address;

        return $this;
    }

    public function getResponsiblePerson(): ?string
    {
        return $this->responsiblePerson;
    }

    public function setResponsiblePerson(?string $responsiblePerson): self
    {
        $this->responsiblePerson = $responsiblePerson;

        return $this;
    }


    public function getCountryCode(): ?string
    {
        return $this->countryCode;
    }

    public function setCountryCode(?string $countryCode): self
    {
        $this->countryCode = $countryCode;

        return $this;
    }

    public function getBankAccount(): ?BankAccount
    {
        return $this->bankAccount;
    }

    public function setBankAccount(?BankAccount $bankAccount): self
    {
        $this->bankAccount = $bankAccount;

        return $this;
    }

    /**
     * Get the value of isPaid
     */ 
    public function getIsPaid()
    {
        return $this->isPaid;
    }

    /**
     * Set the value of isPaid
     *
     * @return  self
     */ 
    public function setIsPaid($isPaid)
    {
        $this->isPaid = $isPaid;

        return $this;
    }

    /**
     * Get the value of isPosted
     */ 
    public function getIsPosted()
    {
        return $this->isPosted;
    }

    /**
     * Set the value of isPosted
     *
     * @return  self
     */ 
    public function setIsPosted($isPosted)
    {
        $this->isPosted = $isPosted;

        return $this;
    }

    /**
     * Get the value of noteEng
     */ 
    public function getNoteEng()
    {
        return $this->noteEng;
    }

    /**
     * Set the value of noteEng
     *
     * @return  self
     */ 
    public function setNoteEng($noteEng)
    {
        $this->noteEng = $noteEng;

        return $this;
    }

    /**
     * @return Collection<int, InvoicePayment>
     */
    public function getInvoicePayments(): Collection
    {
        return $this->invoicePayments;
    }

    public function addInvoicePayment(InvoicePayment $invoicePayment): static
    {
        if (!$this->invoicePayments->contains($invoicePayment)) {
            $this->invoicePayments->add($invoicePayment);
            $invoicePayment->setInvoice($this);
        }

        return $this;
    }

    public function removeInvoicePayment(InvoicePayment $invoicePayment): static
    {
        if ($this->invoicePayments->removeElement($invoicePayment)) {
            // set the owning side to null (unless already changed)
            if ($invoicePayment->getInvoice() === $this) {
                $invoicePayment->setInvoice(null);
            }
        }

        return $this;
    }
}
