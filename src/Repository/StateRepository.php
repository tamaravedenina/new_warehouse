<?php

namespace App\Repository;

use App\Model\Transaction;

class StateRepository extends AbstractRepository
{
    /**
     * @param int $warehouseId
     * @param int $productId
     *
     * @return int
     */
    public function getTodayQuantity($warehouseId, $productId)
    {
        $quantity = $this->dbConnection->fetchColumn(
            'SELECT quantity FROM State WHERE warehouseId = ? AND productId = ? AND date = ?',
            [
                $warehouseId,
                $productId,
                date('Y-m-d')
            ]
        );
        if ($quantity === false) {
            return -1;
        }
        return (int)$quantity;
    }
    /**
     * @param int $warehouseId
     * @param int $productId
     *
     * @return int
     */
    public function getLastQuantity($warehouseId, $productId)
    {
        $quantity = $this->dbConnection->fetchColumn(
            'SELECT quantity FROM State WHERE warehouseId = ? AND productId = ? ORDER BY date DESC',
            [
                $warehouseId,
                $productId
            ]
        );

        if ($quantity === false) {
            return -1;
        }
        return (int)$quantity;

    }
    /**
     * @param int $warehouseId
     * @param int $productId
     * @param int $quantity
     */
    public function update($warehouseId, $productId, $quantity)
    {
        $this->dbConnection->update(
            'State',
            ['quantity' => $quantity],
            [
                'warehouseId' => $warehouseId,
                'productId' => $productId,
                'date' => date('Y-m-d')
            ]
        );
    }
    /**
     * @param int $warehouseId
     * @param int $productId
     * @param int $quantity
     */
    public function insert($warehouseId, $productId, $quantity)
    {
        $this->dbConnection->insert(
            'State',
            [
                'warehouseId' => $warehouseId,
                'productId' => $productId,
                'quantity' => $quantity,
                'date' => date('Y-m-d')
            ]
        );
    }
    /**
     * @param int $warehouseId
     * @param int $productId
     *
     * @throws \Doctrine\DBAL\Exception\InvalidArgumentException
     */
    public function delete($warehouseId, $productId)
    {
        $this->dbConnection->delete(
            'State',
            [
                'warehouseId' => $warehouseId,
                'productId' => $productId,
                'date' => date('Y-m-d')
            ]
        );
    }

    /**
     * @param Transaction[] $transactions
     * @param int $warehouseId
     */
    public function addProducts($transactions, $warehouseId)
    {
        foreach ($transactions as $transaction) {
            $productId = $transaction->getProductId();
            $quantity = $transaction->getQuantity();
            $todayQuantity = $this->getTodayQuantity($warehouseId, $productId);

            if ($todayQuantity >= 0) {
                $this->update($warehouseId, $productId, $quantity + $todayQuantity);
            } else {
                $lastQuantity = $this->getLastQuantity($warehouseId, $productId);
                if ($lastQuantity > 0) {
                    $this->insert($warehouseId, $productId, $lastQuantity + $quantity);
                } else {
                    $this->insert($warehouseId, $productId, $quantity);
                }
            }
        }
    }

    /**
     * @param Transaction[] $transactions
     * @param int $warehouseId
     *
     * @throws \Doctrine\DBAL\ConnectionException
     */
    public function removeProducts($transactions, $warehouseId)
    {
        $this->dbConnection->beginTransaction();
        try {
            foreach ($transactions as $transaction) {
                $productId = $transaction->getProductId();
                $quantity = $transaction->getQuantity();
                $todayQuantity = $this->getTodayQuantity($warehouseId, $productId);

                if ($todayQuantity > 0) {
                    $this->update($warehouseId, $productId, $todayQuantity - $quantity);

                } else {
                    $lastQuantity = $this->getLastQuantity($warehouseId, $productId);
                    if ($lastQuantity > 0) {
                        $this->insert($warehouseId, $productId, $lastQuantity - $quantity);
                    }
                }

                if ($this->getLastQuantity($warehouseId, $productId) < 0) {
                    throw new \LogicException(
                        'not enough product in warehouse!',
                        400
                    );
                }
            }
            $this->dbConnection->commit();
        } catch (\Exception $e) {
            $this->dbConnection->rollBack();
            throw new $e;
        }

    }

    /**
     * @param $warehouseId
     * @param $productId
     * @param $quantity
     * @param $newWarehouseId
     * @throws \Exception
     */
    public function movementProducts($warehouseId, $productId, $quantity, $newWarehouseId)
    {
        $this->removeProducts($warehouseId, $productId, $quantity);
        $this->addProducts($newWarehouseId, $productId, $quantity);
    }
    /**
     * @param int $warehouseId
     *
     * @return int
     */
    public function getFilling($warehouseId)
    {
        $rows = $this->dbConnection->fetchAll(
            'SELECT s1.productId, s1.quantity, p.size
            FROM State AS s1
            JOIN Products AS p ON p.id = s1.productId
            WHERE warehouseId = ? AND date = (
              SELECT MAX(s2.date)
              FROM State AS s2
              WHERE s1.productId = s2.productId AND warehouseId = ? 
            )',
            [
                $warehouseId,
                $warehouseId
            ]
        );

        $filling = 0;
        foreach ($rows as $row) {
            $filling += (int)$row['size'] * (int)$row['quantity'];
        }

        return $filling;
    }
    /**
     * @param $warehouseId
     *
     * @return array
     */
    public function getResiduesByWarehouse($warehouseId)
    {
        $rows = $this->dbConnection->fetchAll(
            'SELECT p.name, s1.productId, s1.quantity, p.price
            FROM State AS s1
            JOIN Products AS p ON p.id = s1.productId
            WHERE warehouseId = ? AND date = (
              SELECT MAX(s2.date)
              FROM State AS s2
              WHERE s1.productId = s2.productId AND warehouseId = ? 
            )',
            [
                $warehouseId,
                $warehouseId
            ]
        );

        $products = [];

        foreach ($rows as $row) {
            $products[] = [
                'productId' => $row['productId'],
                'name' => $row['name'],
                'quantity' => $row['quantity'],
                'cost' => $row['price'] * $row['quantity']
            ];
        }
        return $products;
    }
    /**
     * @param $productId
     *
     * @return array
     */
    public function getResiduesByProduct($productId)
    {
        $rows = $this->dbConnection->fetchAll(
            'SELECT s1.warehouseId, s1.quantity, p.price
            FROM State AS s1
            JOIN Products AS p ON p.id = s1.productId
            WHERE s1.productId = ? AND date = (
              SELECT MAX(s2.date)
              FROM State AS s2
              WHERE s1.productId = s2.productId 
            )',
            [$productId]
        );

        $products = [];

        foreach ($rows as $row) {
            $products[] = [
                'warehouseId' => $row['warehouseId'],
                'quantity' => $row['quantity'],
                'cost' => $row['price'] * $row['quantity']
            ];
        }
        return $products;
    }
    /**
     * @param $warehouseId
     * @param $date
     *
     * @return array
     */
    public function getResiduesByWarehouseForDate($warehouseId, $date)
    {
        $rows = $this->dbConnection->fetchAll(
            'SELECT p.name, s1.productId, s1.quantity, p.price
            FROM State AS s1
            JOIN Products AS p ON p.id = s1.productId
            WHERE warehouseId = ? AND date = (
              SELECT MAX(s2.date)
              FROM State AS s2
              WHERE s1.productId = s2.productId AND s2.warehouseId = ? AND s2.date <= ?
            )',
            [
                $warehouseId,
                $warehouseId,
                $date
            ]
        );

        $products = [];

        foreach ($rows as $row) {
            $products[] = [
                'productId' => $row['productId'],
                'name' => $row['name'],
                'quantity' => $row['quantity'],
                'cost' => $row['price'] * $row['quantity']
            ];
        }
        return $products;
    }
    /**
     * @param $productId
     * @param $date
     *
     * @return array
     */
    public function getResiduesByProductForDate($productId, $date)
    {
        $rows = $this->dbConnection->fetchAll(
            'SELECT s1.warehouseId, s1.quantity, p.price
            FROM State AS s1
            JOIN Products AS p ON p.id = s1.productId
            WHERE s1.productId = ? AND date = (
              SELECT MAX(s2.date)
              FROM State AS s2
              WHERE s1.productId = s2.productId AND s2.date <= ?
            )',
            [
                $productId,
                $date
            ]
        );

        $products = [];

        foreach ($rows as $row) {
            $products[] = [
                'warehouseId' => $row['warehouseId'],
                'quantity' => $row['quantity'],
                'cost' => $row['price'] * $row['quantity']
            ];
        }
        return $products;

    }
}