<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2018-2019 Carlos Garcia Gomez <carlos@facturascripts.com>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */
namespace FacturaScripts\Core\Lib\Accounting;

use FacturaScripts\Dinamic\Lib\Accounting\AccountingAccounts;
use FacturaScripts\Dinamic\Model\Asiento;
use FacturaScripts\Dinamic\Model\Ejercicio;
use FacturaScripts\Dinamic\Model\Subcuenta;

/**
 * Perform regularization of account balances for the exercise.
 *
 * @author Artex Trading sa     <jcuello@artextrading.com>
 */
class AccountingClosingRegularization extends AccountingClosingBase
{

    /**
     * Profit and Loss sub-account
     *
     * @var Subcuenta
     */
    protected $subAccount;

    /**
     * Delete main process.
     * Delete closing regularization accounting entry from exercise.
     *
     * @param Ejercicio $exercise
     * @return boolean
     */
    public function delete($exercise): bool
    {
        return parent::delete($exercise, Asiento::OPERATION_REGULARIZATION);
    }

    /**
     * Execute main process.
     * Create a new account entry for channel with a one line by account balance.
     *
     * @param Ejercicio $exercise
     * @param int       $idjournal
     * @return boolean
     */
    public function exec($exercise, $idjournal): bool
    {
        if (!$this->loadSubAccount($exercise)) {
            return false;
        }
        return $this->delete($exercise) && parent::exec($exercise, $idjournal);
    }

    /**
     * Get the concept for the accounting entry and its lines.
     *
     * @return string
     */
    protected function getConcept(): string
    {
        return $this->toolBox()->i18n()->trans(
            'closing-regularization-concept',
            ['%exercise%' => $this->exercise->nombre]
        );
    }

    /**
     * Get the date for the accounting entry.
     *
     * @return date
     */
    protected function getDate()
    {
        return $this->exercise->fechafin;
    }

    /**
     * Get the special operation identifier for the accounting entry.
     *
     * @return string
     */
    protected function getOperation(): string
    {
        return Asiento::OPERATION_REGULARIZATION;
    }

    /**
     * Get the sub accounts filter for obtain balance.
     *
     * @return string
     */
    protected function getSubAccountsFilter(): string
    {
        return "AND t2.codsubcuenta BETWEEN '6' AND '799999999999999'";
    }

    /**
     * Load profit and loss subaccount
     *
     * @param Ejercicio $exercise
     * @return bool
     */
    protected function loadSubAccount($exercise): bool
    {
        $accounting = new AccountingAccounts();
        $accounting->exercise = $exercise;
        $this->subAccount = $accounting->getSpecialSubAccount(AccountingAccounts::SPECIAL_PROFIT_LOSS_ACCOUNT);
        if (empty($this->subAccount->idsubcuenta)) {
            $this->toolBox()->log()->error('subaccount-pyg-not-found');
            return false;
        }
        return true;
    }

    /**
     * Add regularization lines to account entry.
     *
     * @param Asiento $accountEntry
     * @param float   $debit
     * @param float   $credit
     * @return bool
     */
    protected function saveBalanceLine($accountEntry, $debit, $credit): bool
    {
        if ($debit > $credit) {
           return $this->addLine($accountEntry, 0.00, $debit - $credit);
        }

        return $this->addLine($accountEntry, $credit - $debit, 0.00);
    }

    /**
     * Establishes the common data of the entries of the accounting entry
     *
     * @param Partida $line
     * @param array   $data
     */
    protected function setDataLine(&$line, $data)
    {
        parent::setDataLine($line, $data);
        if ($data['debit'] > $data['credit']) {
            $line->haber = $data['debit'] - $data['credit'];
            return;
        }
        $line->debe = $data['credit'] - $data['debit'];
    }

    /**
     *
     * @param Asiento $accountEntry
     * @param float   $debit
     * @param float   $credit
     * @return bool
     */
    private function addLine($accountEntry, $debit, $credit): bool
    {
        $data = [
            'id' => $this->subAccount->idsubcuenta,
            'code' => $this->subAccount->codsubcuenta,
            'debit' => $debit,
            'credit' => $credit
        ];

        $line = $accountEntry->getNewLine();
        $this->setDataLine($line, $data);
        return $line->save();
    }
}
