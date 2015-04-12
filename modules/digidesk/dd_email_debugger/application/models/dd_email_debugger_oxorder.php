<?php
/**
 *
 *     |o     o    |          |
 * ,---|.,---..,---|,---.,---.|__/
 * |   |||   |||   ||---'`---.|  \
 * `---'``---|``---'`---'`---'`   `
 *       `---'    [media solutions]
 *
 * @copyright   (c) digidesk - media solutions
 * @link        http://www.digidesk.de
 * @author      digidesk - media solutions
 * @version     Git: $Id$
 */

class dd_email_debugger_oxorder extends dd_email_debugger_oxorder_parent
{
    public function setBasket( $oBasket )
    {
        /** @var oxOrder $this */
        $this->_oBasket = $oBasket;
    }

    public function setPayment()
    {
        /** @var oxOrder $this */
        $this->_oPayment = $this->_setPayment( $this->oxorder__oxpaymenttype->value );
    }
}