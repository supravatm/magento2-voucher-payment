<?php

declare(strict_types=1);

namespace SMG\VoucherPayment\Plugin;

use Psr\Log\LoggerInterface;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Framework\Exception\LocalizedException;

use SMG\VoucherPayment\Api\VoucherRepositoryInterface;

/**
 * Save Voucher numner to quote
 */

class PaymentInformationManagementPlugin
{
    /**
     *
     * @var LoggerInterface
     */
    protected LoggerInterface $logger;

    /**
     *
     * @var CartRepositoryInterface
     */
    private CartRepositoryInterface $quoteRepository;

    /**
     *
     * @var VoucherRepositoryInterface
     */
    private VoucherRepositoryInterface $voucherRepository;

    /**
     * @param LoggerInterface $logger
     * @param CartRepositoryInterface $quoteRepository
     * @param VoucherRepositoryInterface $voucherRepository
     */
    public function __construct(
        LoggerInterface $logger,
        CartRepositoryInterface $quoteRepository,
        VoucherRepositoryInterface $voucherRepository
    ) {
        $this->logger = $logger;
        $this->quoteRepository = $quoteRepository;
        $this->voucherRepository = $voucherRepository;
    }

    /**
     * @inheritdoc
     */
    public function beforeSavePaymentInformation(
        \Magento\Checkout\Model\PaymentInformationManagement $subject,
        int $cartId,
        \Magento\Quote\Api\Data\PaymentInterface $paymentMethod,
        $billingAddress = null
    ) {

        $extensionAttributes = $paymentMethod->getExtensionAttributes();
        if ($extensionAttributes && $extensionAttributes->getVoucherNumber()) {

            $voucherNumber = trim($extensionAttributes->getVoucherNumber());

            // Validate voucher
            $voucher = $this->voucherRepository->getByVoucherNumber($voucherNumber);
            if (!$voucher || !$voucher->getId() || !$voucher->getIsActive()
                || $voucher->getTimesUsed() >= $voucher->getUsageLimit()
            ) {
                throw new \Magento\Framework\Exception\LocalizedException(
                    __('The voucher number is invalid or has expired.')
                );
            }

            if ($voucherNumber) {
                throw new LocalizedException(
                    __('The voucher number is invalid or has expired.')
                );
            }

            $quote = $this->quoteRepository->getActive($cartId);

            $quote->getPayment()->setData(
                'voucher_number',
                $extensionAttributes->getVoucherNumber()
            );

            $this->quoteRepository->save($quote);
        }
        return [$cartId, $paymentMethod, $billingAddress];
    }
}
