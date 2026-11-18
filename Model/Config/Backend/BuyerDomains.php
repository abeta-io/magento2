<?php declare(strict_types=1);

namespace Abeta\PunchOut\Model\Config\Backend;

use Magento\Config\Model\Config\Backend\Serialized\ArraySerialized;
use Magento\Framework\Exception\LocalizedException;

/**
 * Validates and normalizes the master account => allowed buyer domains rows before saving
 */
class BuyerDomains extends ArraySerialized
{

    /**
     * @return $this
     * @throws LocalizedException
     */
    public function beforeSave()
    {
        $value = $this->getValue();
        if (!is_array($value)) {
            return parent::beforeSave();
        }

        $rows = [];
        foreach ($value as $key => $row) {
            if (!is_array($row)) {
                continue;
            }

            $email = strtolower(trim((string)($row['master_email'] ?? '')));
            $domainsInput = trim((string)($row['allowed_domains'] ?? ''));
            if ($email === '' && $domainsInput === '') {
                continue;
            }

            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                throw new LocalizedException(
                    __('"%1" is not a valid master account email address.', $email)
                );
            }

            $domains = [];
            foreach (self::splitDomains($domainsInput) as $domain) {
                if (!self::isValidDomain($domain)) {
                    throw new LocalizedException(
                        __('"%1" is not a valid buyer domain for %2.', $domain, $email)
                    );
                }
                $domains[$domain] = $domain;
            }

            if (empty($domains)) {
                throw new LocalizedException(
                    __('Enter at least one allowed buyer domain for %1.', $email)
                );
            }

            $rows[$key] = [
                'master_email' => $email,
                'allowed_domains' => implode(', ', $domains),
            ];
        }

        $this->setValue($rows);

        return parent::beforeSave();
    }

    /**
     * Split a comma, semicolon or whitespace separated domain list into lowercase domains
     * without a leading "@".
     *
     * @param string $input
     * @return string[]
     */
    public static function splitDomains(string $input): array
    {
        $domains = [];
        foreach (preg_split('/[\s,;]+/', $input, -1, PREG_SPLIT_NO_EMPTY) ?: [] as $domain) {
            $domain = strtolower(ltrim($domain, '@'));
            if ($domain !== '') {
                $domains[] = $domain;
            }
        }

        return $domains;
    }

    /**
     * @param string $domain
     * @return bool
     */
    private static function isValidDomain(string $domain): bool
    {
        return strpos($domain, '.') !== false
            && filter_var($domain, FILTER_VALIDATE_DOMAIN, FILTER_FLAG_HOSTNAME) !== false;
    }
}
