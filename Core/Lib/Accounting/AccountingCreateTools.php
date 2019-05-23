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

use FacturaScripts\Core\Base\DataBase\DataBaseWhere;
use FacturaScripts\Dinamic\Model\Cliente;
use FacturaScripts\Dinamic\Model\Cuenta;
use FacturaScripts\Dinamic\Model\Ejercicio;
use FacturaScripts\Dinamic\Model\Proveedor;
use FacturaScripts\Dinamic\Model\Subcuenta;

/**
 * Class to create accounting sub-accounts automatically:
 *
 *   - General
 *   - Customer
 *   - Supplier
 *
 * @author Artex Trading sa     <jcuello@artextrading.com>
 * @author Carlos García Gómez  <carlos@facturascripts.com>
 */
class AccountingCreateTools
{

    /**
     *
     * @var Ejercicio
     */
    private $exercise;

    /**
     * Class constructor
     *
     * @param string $exercise
     */
    public function __construct()
    {
        $this->exercise = new Ejercicio();
    }

    /**
     * Load exercise and check if it can be used
     *
     * @param string $code
     * @return bool
     */
    private function checkExercise(string $code): bool
    {
        if ($this->exercise->codejercicio !== $code) {
            $this->exercise->loadFromCode($code);
        }

        return $this->exercise->isOpened();
    }

    /**
     * Create the accounting sub-account for the informed customer or supplier.
     * If the customer or supplier does not have an associated accounting subaccount,
     * one is calculated automatically.
     *
     * @param Cliente|Proveedor $business     Customer or Supplier model
     * @param Cuenta $account                 Parent group account model
     *
     * @return Subcuenta
     */
    public function createBusinessAccount(&$business, $account)
    {
        if (!$account->exists() || !$this->checkExercise($account->codejercicio)) {
            return new Subcuenta();
        }

        if (empty($business->codsubcuenta)) {
            $business->codsubcuenta = $this->getFreeBusinessSubaccount($business, $account);
        }

        $subaccount = new Subcuenta();
        $subaccount->codcuenta = $account->codcuenta;
        $subaccount->codejercicio = $account->codejercicio;
        $subaccount->codsubcuenta = $business->codsubcuenta;
        $subaccount->descripcion = $business->razonsocial;
        $subaccount->idcuenta = $account->idcuenta;
        if ($subaccount->save()) {
            $business->save();
        }
        return $subaccount;
    }

    /**
     * Create a sub-account with the code and the reported description
     * belonging to the group and exercise.
     *
     * @param Cuenta $account        Parent group account
     * @param String $code           The code of the subaccount
     * @param String $description    The description of the subaccount
     * @return Subcuenta
     */
    public function createFromAccount($account, $code, $description)
    {
        if (!$account->exists() || !$this->checkExercise($account->codejercicio)) {
            return new Subcuenta();
        }

        $subaccount = new Subcuenta();
        $subaccount->codcuenta = $account->codcuenta;
        $subaccount->codejercicio = $account->codejercicio;
        $subaccount->codsubcuenta = $code;
        $subaccount->descripcion = $description;
        $subaccount->idcuenta = $account->idcuenta;
        $subaccount->save();
        return $subaccount;
    }

    /**
     * Complete to the indicated length with zeros.
     *
     * @param int    $length
     * @param string $value
     * @param string $prefix
     *
     * @return string
     */
    public function fillToLength(int $length, string $value, string $prefix = ''): string
    {
        $value2 = trim($value);
        $count = $length - strlen($prefix) - strlen($value2);
        if ($count > 0) {
            return $prefix . str_repeat('0', $count) . $value2;
        } elseif ($count == 0) {
            return $prefix . $value2;
        }

        return '';
    }

    /**
     * Calculate an accounting sub-account from the customer or supplier code
     *
     * @param Cliente|Proveedor $business     Customer or Supplier model
     * @param Cuenta $account                 Parent group account model
     *
     * @return string
     */
    public function getFreeBusinessSubaccount($business, $account)
    {
        if (!$this->checkExercise($account->codejercicio)) {
            return '';
        }

        $subAccount = new Subcuenta();
        $numbers = array_merge([$business->primaryColumnValue()], range(1, 999));
        foreach ($numbers as $num) {
            $newCode = $this->fillToLength($this->exercise->longsubcuenta, $num, $account->codcuenta);
            if (empty($newCode) || ($business->count([new DataBaseWhere('codsubcuenta', $newCode)]) > 0)) {
                continue;
            }

            $where = [
                new DataBaseWhere('codejercicio', $this->exercise->codejercicio),
                new DataBaseWhere('codsubcuenta', $newCode)
            ];
            if ($subAccount->loadFromCode('', $where)) {
                return $newCode;
            }
        }
        return '';
    }
}