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
/**
 * @package admin
 */
class dd_email_debugger extends oxAdminView
{
    /**
     * Current class template name.
     * @var string
     */
    protected $_sThisTemplate = 'dd_email_debugger.tpl';


    /**
     * @var oxConfig
     */
    protected $_oConf = null;


    protected $_aMailTemplates = array(
        'order_cust'        => 'Bestellbestätigung an den Kunden',
        'order_owner'       => 'Bestellbestätigung an den Shopbetreiber',
        'forgotpwd'         => 'Passwort vergessen',
        'invite'            => 'Einladung',
        'newsletteroptin'   => 'Newsletter Opt-In',
        'ordershipped'      => 'Versandmitteilung',
        'owner_reminder'    => 'Lagerbestand niedrig',
        'pricealarm_owner'  => 'Preisalarm an den Shopbetreiber',
        'register'          => 'Registrierung',
        'senddownloadlinks' => 'Downloadlinks',
        'suggest'           => 'Produktempfehlung',
        'wishlist'          => 'Wunschzettel',
    );


    public function init()
    {
        parent::init();

        $this->_oConf = $this->getConfig();
    }


    /**
     * @return array
     */
    public function getMailTemplates()
    {
        return $this->_aMailTemplates;
    }


    public function sendMail()
    {
        /** @var oxEmail $oEmail */
        $oEmail              = oxNew( 'oxEmail' );
        $oUser               = $this->getUser();
        $aPost               = $this->_oConf->getRequestParameter( 'editval' );
        $blPrepareSuccessful = false;

        switch( $aPost[ 'template' ] )
        {
            case 'order_cust':
                $blPrepareSuccessful = $oEmail->sendDummyOrderEmailToUser();
                break;
            case 'order_owner':
                $blPrepareSuccessful = $oEmail->sendDummyOrderEmailToOwner();
                break;
            case 'forgotpwd':
                $blPrepareSuccessful = $oEmail->sendDummyForgotPwdEmail();
                break;
            case 'invite':
                $blPrepareSuccessful = $oEmail->sendDummyInviteMail();
                break;
            case 'newsletteroptin':
                $blPrepareSuccessful = $oEmail->sendDummyNewsletterDbOptInMail();
                break;
            case 'ordershipped':
                $blPrepareSuccessful = $oEmail->sendDummySendedNowMail();
                break;
            case 'owner_reminder':
                $blPrepareSuccessful = $oEmail->sendDummyStockReminder();
                break;
            case 'pricealarm_owner':
                $blPrepareSuccessful = $oEmail->sendDummyPriceAlarmNotification();
                break;
            case 'register':
                $blPrepareSuccessful = $oEmail->sendDummyRegisterEmail();
                break;
            case 'senddownloadlinks':
                $blPrepareSuccessful = $oEmail->sendDummyDownloadLinksMail();
                break;
            case 'suggest':
                $blPrepareSuccessful = $oEmail->sendDummySuggestMail();
                break;
            case 'wishlist':
                $blPrepareSuccessful = $oEmail->sendDummyWishlistMail();
                break;
        }

        if( $blPrepareSuccessful )
        {
            if( isset( $aPost[ 'html_preview' ] ) || isset( $aPost[ 'plain_preview' ] ) || isset( $aPost[ 'preview' ] ) )
            {
                if( isset( $aPost[ 'iframe' ] ) )
                {
                    if( $aPost[ 'preview' ] == 'html' )
                    {
                        echo $oEmail->getBody();
                    }
                    else
                    {
                        echo '<pre>';
                        echo $oEmail->getAltBody();
                        echo '</pre>';
                    }
                    exit;
                }
            }
            else
            {
                $sFullName = $oUser->oxuser__oxfname->getRawValue() . " " . $oUser->oxuser__oxlname->getRawValue();
                $oEmail->setRecipient( $aPost[ 'receiver' ], $sFullName );

                $oEmail->send();
            }
        }
    }
}