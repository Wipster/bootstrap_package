<?php
declare(strict_types=1);

/*
 * This file is part of the package bk2k/bootstrap-package.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace BK2K\BootstrapPackage\Updates;

use Doctrine\DBAL\ForwardCompatibility\Result;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\Restriction\DeletedRestriction;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Install\Updates\DatabaseUpdatedPrerequisite;
use TYPO3\CMS\Install\Updates\RepeatableInterface;
use TYPO3\CMS\Install\Updates\UpgradeWizardInterface;

/**
 * TexticonIconUpdate
 */
class TexticonIconUpdate implements UpgradeWizardInterface, RepeatableInterface
{
    /**
     * @var string
     */
    protected $table = 'tt_content';

    /**
     * @var string
     */
    protected $field = 'icon';

    /**
    * @return string
    */
    public function getIdentifier(): string
    {
        return self::class;
    }

    /**
     * @return string
     */
    public function getTitle(): string
    {
        return '[Bootstrap Package] Migrate text and icon identifier and name';
    }

    /**
     * @return string
     */
    public function getDescription(): string
    {
        return '';
    }

    /**
     * @return string[]
     */
    public function getPrerequisites(): array
    {
        return [
            DatabaseUpdatedPrerequisite::class
        ];
    }

    /**
     * @return bool
     */
    public function updateNecessary(): bool
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable($this->table);
        $queryBuilder->getRestrictions()->removeAll()->add(GeneralUtility::makeInstance(DeletedRestriction::class));
        /** @var Result $result */
        $result = $queryBuilder->count('uid')
            ->from($this->table)
            ->orWhere(
                $queryBuilder->expr()->like(
                    $this->field,
                    $queryBuilder->expr()->literal('Glyphicons%')
                ),
                $queryBuilder->expr()->like(
                    $this->field,
                    $queryBuilder->expr()->literal('Ionicons%')
                )
            )
            ->execute();
        return (bool) $result->fetchOne();
    }

    /**
     * @return bool
     */
    public function executeUpdate(): bool
    {
        $connection = GeneralUtility::makeInstance(ConnectionPool::class)->getConnectionForTable($this->table);
        $queryBuilder = $connection->createQueryBuilder();
        $queryBuilder->getRestrictions()->removeAll()->add(GeneralUtility::makeInstance(DeletedRestriction::class));
        /** @var Result $result */
        $result = $queryBuilder->select('uid', $this->field)
            ->from($this->table)
            ->orWhere(
                $queryBuilder->expr()->like(
                    $this->field,
                    $queryBuilder->expr()->literal('Glyphicons%')
                ),
                $queryBuilder->expr()->like(
                    $this->field,
                    $queryBuilder->expr()->literal('Ionicons%')
                )
            )
            ->execute();
        while ($record = $result->fetchAssociative()) {
            $icon = explode('__', strval($record[$this->field]));
            $queryBuilder = $connection->createQueryBuilder();
            $queryBuilder->update($this->table)
                ->where(
                    $queryBuilder->expr()->eq(
                        'uid',
                        $queryBuilder->createNamedParameter($record['uid'], \PDO::PARAM_INT)
                    )
                )
                ->set(
                    'icon_set',
                    'EXT:bootstrap_package/Resources/Public/Images/Icons/' . $icon[0] . '/'
                )
                ->set(
                    $this->field,
                    'EXT:bootstrap_package/Resources/Public/Images/Icons/' . $icon[0] . '/' . $icon[1] . '.svg'
                );
            $queryBuilder->execute();
        }
        return true;
    }
}
