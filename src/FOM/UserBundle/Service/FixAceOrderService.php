<?php

namespace FOM\UserBundle\Service;

use Doctrine\DBAL\Driver\PDO\PgSQL\Driver as PgSQLDriver;
use Doctrine\DBAL\Driver\PDO\SQLite\Driver as SQLiteDriver;
use Doctrine\ORM\EntityManagerInterface;

/**
 * The (deprecated) symfony/acl-bundle has a bug that prevents an access control list (ACL) from being saved,
 * when a security identify (e.g. a user) was deleted that had an entry in the component's ACL and was not the
 * last one in the list. The ACL expects the ace_order column to start with zero and increase one by one,
 * otherwise array indexing fails.
 * For example: An application has access rights for user A, B and C. User B is deleted. Then, the acl_entries
 * table will have only entries for users A (ace_order 0) and C (ace_order 2) which can't be saved anymore
 * This service goes through the whole acl_entries table and resets the indices of entries where the ace_order
 * contains gaps.
 */
class FixAceOrderService
{
    private EntityManagerInterface $em;

    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
    }

    public function fixAceOrder(): void
    {
        $connection = $this->em->getConnection();

        $result = $connection->executeQuery('SELECT * FROM acl_entries ORDER BY class_id, object_identity_id, ace_order')->fetchAllAssociative();
        $previousRow = null;
        $expectedOrder = 0;

        $stmt = $connection->prepare('UPDATE acl_entries SET ace_order = :neworder WHERE id = :id');
        foreach ($result as $row) {
            if ($this->sameEntry($previousRow, $row)) {
                $expectedOrder++;
            } else {
                $expectedOrder = 0;
            }
            if ($row['ace_order'] !== $expectedOrder) {
                $stmt->bindValue(':neworder', $expectedOrder);
                $stmt->bindValue(':id', $row['id']);
                $stmt->executeStatement();
            }
            $previousRow = $row;
        }
    }

    private function sameEntry(?array $row1, array $row2): bool
    {
        if ($row1 === null) return false;
        if ($row1['class_id'] !== $row2['class_id']) return false;
        if ($row1['object_identity_id'] !== $row2['object_identity_id']) return false;
        return true;
    }

}
