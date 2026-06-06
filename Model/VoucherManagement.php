<?php

declare(strict_types=1);

namespace SMG\VoucherPayment\Model;

use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use SMG\VoucherPayment\Api\VoucherManagementInterface;
use SMG\VoucherPayment\Api\VoucherRepositoryInterface;
use SMG\VoucherPayment\Api\Data\VoucherInterface;

class VoucherManagement implements VoucherManagementInterface
{
    /**
     * @param VoucherRepositoryInterface $voucherRepository
     */
    public function __construct(
        private readonly VoucherRepositoryInterface $voucherRepository
    ) {
    }

    /**
     * Validate voucher number.
     *
     * @param string $voucherNumber
     * @return bool
     * @throws LocalizedException
     */
    public function validate(string $voucherNumber): bool
    {
        try {
            $voucher = $this->voucherRepository
                ->getByVoucherNumber($voucherNumber);
        } catch (NoSuchEntityException $exception) {
            throw new LocalizedException(
                __('The voucher number entered is invalid or expired.')
            );
        }

        $this->validateVoucher($voucher);

        return true;
    }

    /**
     * Validate voucher business rules.
     *
     * @param VoucherInterface $voucher
     * @return void
     * @throws LocalizedException
     */
    private function validateVoucher(
        VoucherInterface $voucher
    ): void {
        if (!$voucher->getIsActive()) {
            throw new LocalizedException(
                __('The voucher number entered is invalid or expired.')
            );
        }

        if ($voucher->getValidFrom()
            && strtotime($voucher->getValidFrom()) > time()
        ) {
            throw new LocalizedException(
                __('The voucher number entered is invalid or expired.')
            );
        }

        if ($voucher->getValidTo()
            && strtotime($voucher->getValidTo()) < time()
        ) {
            throw new LocalizedException(
                __('The voucher number entered is invalid or expired.')
            );
        }

        if ($voucher->getUsageLimit() > 0
            && $voucher->getTimesUsed() >= $voucher->getUsageLimit()
        ) {
            throw new LocalizedException(
                __('The voucher number entered is invalid or expired.')
            );
        }
    }

    /**
     * Retrieve and validate voucher.
     *
     * @param string $voucherNumber
     * @return VoucherInterface
     * @throws LocalizedException
     */
    public function getVoucher(
        string $voucherNumber
    ): VoucherInterface {
        try {
            $voucher = $this->voucherRepository
                ->getByVoucherNumber($voucherNumber);
        } catch (NoSuchEntityException $exception) {
            throw new LocalizedException(
                __('The voucher number entered is invalid or expired.')
            );
        }

        $this->validateVoucher($voucher);

        return $voucher;
    }

    /**
     * Mark voucher as used.
     *
     * @param string $voucherNumber
     * @param int $orderId
     * @return bool
     * @throws LocalizedException
     */
    public function markAsUsed(
        string $voucherNumber,
        int $orderId
    ): bool {
        $voucher = $this->getVoucher($voucherNumber);

        $timesUsed = $voucher->getTimesUsed() + 1;

        $voucher->setTimesUsed($timesUsed);

        if ($voucher->getUsageLimit() > 0
            && $timesUsed >= $voucher->getUsageLimit()
        ) {
            $voucher->setIsActive(false);
        }

        $this->voucherRepository->save($voucher);

        return true;
    }
}
