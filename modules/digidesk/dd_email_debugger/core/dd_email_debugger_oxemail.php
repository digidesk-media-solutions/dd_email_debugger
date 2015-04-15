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

class dd_email_debugger_oxemail extends dd_email_debugger_oxemail_parent
{
    /**
     * Sets mailer additional settings and sends ordering mail.
     * Returns true on success.
     *
     * @return bool
     */
    public function sendDummyOrderEmailToUser()
    {
        /** @var oxEmail $this */
        $oConfig    = $this->getConfig();
        /** @var oxOrder $oOrder */
        $oOrder     = oxNew( 'oxOrder' );
        $sOrderView = getViewName( 'oxorder' );

        $sOXID = oxDb::getDb( true )->getOne( "SELECT `OXID` FROM $sOrderView WHERE `OXSTORNO` = 0 ORDER BY `OXTIMESTAMP` DESC LIMIT 1" );

        if( $oOrder->load( $sOXID ) )
        {
            // add user defined stuff if there is any
            $oOrder = $this->_addUserInfoOrderEMail( $oOrder );

            /** @var oxBasket $oBasket */
            /** @var oxOrderArticle $oOrderArticle */
            $oBasket = oxNew( 'oxBasket' );
            foreach( $oOrder->getOrderArticles() as $oOrderArticle )
            {
                $sProductID = $oOrderArticle->getProductId();
                $dAmount    = $oOrderArticle->oxorderarticles__oxamount->value;
                $aSel       = $oOrderArticle->getOrderArticleSelectList();
                $aPersParam = $oOrderArticle->getPersParams();

                $oBasket->addToBasket( $sProductID, $dAmount, $aSel, $aPersParam );
            }
            $oBasket->calculateBasket( true );
            $oBasket->setPayment( $oOrder->oxorder__oxpaymentid->value );
            $oOrder->setBasket( $oBasket );
            $oOrder->setPayment();

            $oShop = $this->_getShop();
            $this->_setMailParams( $oShop );

            $this->setUser( $oOrder->getOrderUser() );

            // create messages
            $oSmarty = $this->_getSmarty();
            $this->setViewData( "order", $oOrder );

            $oConfig->setAdminMode( false );

            if( $oConfig->getConfigParam( "bl_perfLoadReviews" ) )
            {
                $this->setViewData( "blShowReviewLink", true );
            }

            // Process view data array through oxOutput processor
            $this->_processViewArray();

            $this->setBody( $oSmarty->fetch( $oConfig->getTemplatePath( $this->_sOrderUserTemplate, false ) ) );
            $this->setAltBody( $oSmarty->fetch( $oConfig->getTemplatePath( $this->_sOrderUserPlainTemplate, false ) ) );

            if( $oSmarty->template_exists( $oConfig->getTemplatePath( $this->_sOrderUserSubjectTemplate, false ) ) )
            {
                $sSubject = $oSmarty->fetch( $oConfig->getTemplatePath( $this->_sOrderUserSubjectTemplate, false ));
            }
            else
            {
                $sSubject = $oShop->oxshops__oxordersubject->getRawValue() . " (#" . $oOrder->oxorder__oxordernr->value . ")";
            }

            $this->setSubject( $sSubject );

            $this->setReplyTo( $oShop->oxshops__oxorderemail->value, $oShop->oxshops__oxname->getRawValue() );

            $oConfig->setAdminMode( true );

            return true;
        }

        return false;
    }


    /**
     * Sets mailer additional settings and sends ordering mail to shop owner.
     * Returns true on success.
     *
     * @return bool
     */
    public function sendDummyOrderEmailToOwner()
    {
        $oConfig    = $this->getConfig();
        $oShop      = $this->_getShop();
        /** @var oxOrder $oOrder */
        $oOrder     = oxNew( 'oxOrder' );
        $sOrderView = getViewName( 'oxorder' );

        $sOXID = oxDb::getDb( true )->getOne( "SELECT `OXID` FROM $sOrderView WHERE `OXSTORNO` = 0 ORDER BY `OXTIMESTAMP` DESC LIMIT 1" );

        if( $oOrder->load( $sOXID ) )
        {
            // cleanup
            $this->_clearMailer();

            // add user defined stuff if there is any
            $oOrder = $this->_addUserInfoOrderEMail($oOrder);

            /** @var oxBasket $oBasket */
            /** @var oxOrderArticle $oOrderArticle */
            $oBasket = oxNew( 'oxBasket' );
            foreach( $oOrder->getOrderArticles() as $oOrderArticle )
            {
                $sProductID = $oOrderArticle->getProductId();
                $dAmount    = $oOrderArticle->oxorderarticles__oxamount->value;
                $aSel       = $oOrderArticle->getOrderArticleSelectList();
                $aPersParam = $oOrderArticle->getPersParams();

                $oBasket->addToBasket( $sProductID, $dAmount, $aSel, $aPersParam );
            }
            $oBasket->calculateBasket( true );
            $oBasket->setPayment( $oOrder->oxorder__oxpaymentid->value );
            $oOrder->setBasket( $oBasket );
            $oOrder->setPayment();

            $oUser = $oOrder->getOrderUser();
            $this->setUser($oUser);

            // send confirmation to shop owner
            // send not pretending from order user, as different email domain rise spam filters
            $this->setFrom($oShop->oxshops__oxowneremail->value, $oShop->oxshops__oxname->getRawValue() );

            $oLang = oxRegistry::getLang();
            $iOrderLang = $oLang->getObjectTplLanguage();

            // if running shop language is different from admin lang. set in config
            // we have to load shop in config language
            if ($oShop->getLanguage() != $iOrderLang) {
                $oShop = $this->_getShop($iOrderLang);
            }

            $this->setSmtp($oShop);

            // create messages
            $oSmarty = $this->_getSmarty();
            $this->setViewData("order", $oOrder);

            $oConfig->setAdminMode( false );

            // Process view data array through oxoutput processor
            $this->_processViewArray();

            $this->setBody($oSmarty->fetch($oConfig->getTemplatePath($this->_sOrderOwnerTemplate, false)));
            $this->setAltBody($oSmarty->fetch($oConfig->getTemplatePath($this->_sOrderOwnerPlainTemplate, false)));

            //Sets subject to email
            // #586A
            if ($oSmarty->template_exists($oConfig->getTemplatePath( $this->_sOrderOwnerSubjectTemplate, false)) ) {
                $sSubject = $oSmarty->fetch($oConfig->getTemplatePath( $this->_sOrderOwnerSubjectTemplate, false ) );
            } else {
                $sSubject = $oShop->oxshops__oxordersubject->getRawValue() . " (#" . $oOrder->oxorder__oxordernr->value . ")";
            }

            $this->setSubject($sSubject);

            $oConfig->setAdminMode( true );

            return true;
        }

        return false;
    }


    /**
     * Sets mailer additional settings and sends "forgot password" mail to user.
     * Returns true on success.
     *
     * @return mixed true - success, false - user not found, -1 - could not send
     */
    public function sendDummyForgotPwdEmail()
    {
        /** @var oxEmail $this */
        $oConfig = $this->getConfig();
        $oUser = $oConfig->getUser();

        // shop info
        $oShop = $this->_getShop();

        // add user defined stuff if there is any
        $oShop = $this->_addForgotPwdEmail($oShop);

        //set mail params (from, fromName, smtp)
        $this->_setMailParams($oShop);

        // create messages
        $oSmarty = $this->_getSmarty();

        $oConfig->setAdminMode( false );
        $this->setUser( $oUser );

        // Process view data array through oxoutput processor
        $this->_processViewArray();

        $this->setBody($oSmarty->fetch($this->_sForgotPwdTemplate));
        $this->setAltBody($oSmarty->fetch($this->_sForgotPwdTemplatePlain));

        //sets subject of email
        $this->setSubject( $oShop->oxshops__oxforgotpwdsubject->getRawValue() );
        $this->setReplyTo($oShop->oxshops__oxorderemail->value, $oShop->oxshops__oxname->getRawValue());

        $oConfig->setAdminMode( true );

        return true; // success
    }


    /**
     * Sets mailer additional settings and sends "InviteMail" mail to user.
     * Returns true on success.
     *
     * @return bool
     */
    public function sendDummyInviteMail()
    {
        /** @var oxEmail $this */
        $oConfig = $this->getConfig();

        //sets language of shop
        $iCurrLang = $oConfig->getActiveShop()->getLanguage();

        // shop info
        $oShop = $this->_getShop($iCurrLang);
        $oUser = $oConfig->getUser();
        $oUser->send_name = $oUser->oxuser__oxfname->getRawValue() . " " . $oUser->oxuser__oxlname->getRawValue();
        $oUser->send_email = $oUser->oxuser__oxusername->getRawValue();
        $oUser->send_message = "Dies\r\nist\r\nmeine\r\nEmpfehlung\r\nan\r\ndich.";

        // mailer stuff
        $this->setFrom( $oShop->oxshops__oxowneremail->value, $oShop->oxshops__oxname->getRawValue() );
        $this->setSMTP();

        // create messages
        $oSmarty = $this->_getSmarty();

        $oConfig->setAdminMode( false );
        $this->setUser( $oUser );

        $sHomeUrl = $this->getViewConfig()->getHomeLink();

        $sRegisterUrl = oxRegistry::get("oxUtilsUrl")->appendParamSeparator($sHomeUrl) . "re=" . md5($oShop->oxshops__oxowneremail->value);
        $this->setViewData("sHomeUrl", $sRegisterUrl);

        // Process view data array through oxoutput processor
        $this->_processViewArray();

        $this->setBody($oSmarty->fetch($this->_sInviteTemplate));

        $this->setAltBody($oSmarty->fetch($this->_sInviteTemplatePlain));
        $this->setSubject("Einladung");

        $this->setReplyTo($oShop->oxshops__oxowneremail->value);

        $oConfig->setAdminMode( true );

        return true;
    }


    /**
     * Sets mailer additional settings and sends "NewsletterDBOptInMail" mail to user.
     * Returns true on success.
     *
     * @return bool
     */
    public function sendDummyNewsletterDbOptInMail()
    {
        /** @var oxEmail $this */
        $oConfig = $this->getConfig();
        $oUser = $oConfig->getUser();

        // add user defined stuff if there is any
        $oUser = $this->_addNewsletterDbOptInMail($oUser);

        // shop info
        $oShop = $this->_getShop();

        //set mail params (from, fromName, smtp)
        $this->_setMailParams($oShop);

        // create messages
        $oSmarty = $this->_getSmarty();

        $oConfig->setAdminMode( false );

        $sConfirmCode = md5($oUser->oxuser__oxusername->value . $oUser->oxuser__oxpasssalt->value);
        $this->setViewData("subscribeLink", $this->_getNewsSubsLink($oUser->oxuser__oxid->value, $sConfirmCode));
        $this->setUser($oUser);

        // Process view data array through oxOutput processor
        $this->_processViewArray();

        $this->setBody($oSmarty->fetch($this->_sNewsletterOptInTemplate));
        $this->setAltBody($oSmarty->fetch($this->_sNewsletterOptInTemplatePlain));
        $this->setSubject( oxRegistry::getLang()->translateString("NEWSLETTER") . " " . $oShop->oxshops__oxname->getRawValue() );

        $this->setFrom($oShop->oxshops__oxinfoemail->value, $oShop->oxshops__oxname->getRawValue());
        $this->setReplyTo($oShop->oxshops__oxinfoemail->value, $oShop->oxshops__oxname->getRawValue());

        $oConfig->setAdminMode( true );

        return true;
    }


    /**
     * Sets mailer additional settings and sends "SendedNowMail" mail to user.
     * Returns true on success.
     *
     * @return bool
     */
    public function sendDummySendedNowMail()
    {
        /** @var oxEmail $this */
        $oConfig = $this->getConfig();

        $oOrder     = oxNew( 'oxOrder' );
        $sOrderView = getViewName( 'oxorder' );

        $sOXID = oxDb::getDb( true )->getOne( "SELECT `OXID` FROM $sOrderView WHERE `OXSTORNO` = 0 ORDER BY `OXTIMESTAMP` DESC LIMIT 1" );

        if( $oOrder->load( $sOXID ) )
        {
            $iOrderLang = (int) (isset($oOrder->oxorder__oxlang->value) ? $oOrder->oxorder__oxlang->value : 0);

            // shop info
            $oShop = $this->_getShop($iOrderLang);

            //set mail params (from, fromName, smtp)
            $this->_setMailParams($oShop);

            //create messages
            $oLang = oxRegistry::getLang();
            $oSmarty = $this->_getSmarty();
            $this->setViewData("order", $oOrder);
            $this->setViewData("shopTemplateDir", $oConfig->getTemplateDir(false));

            if ($oConfig->getConfigParam("bl_perfLoadReviews")) {
                $this->setViewData("blShowReviewLink", true);
                $oUser = oxNew('oxuser');
                $this->setViewData("reviewuserhash", $oUser->getReviewUserHash($oOrder->oxorder__oxuserid->value));
            }

            // Process view data array through oxoutput processor
            $this->_processViewArray();

            // #1469 - we need to patch security here as we do not use standard template dir, so smarty stops working
            $aStore['INCLUDE_ANY'] = $oSmarty->security_settings['INCLUDE_ANY'];
            //V send email in order language
            $iOldTplLang = $oLang->getTplLanguage();
            $iOldBaseLang = $oLang->getTplLanguage();
            $oLang->setTplLanguage($iOrderLang);
            $oLang->setBaseLanguage($iOrderLang);

            $oSmarty->security_settings['INCLUDE_ANY'] = true;
            // force non admin to get correct paths (tpl, img)
            $oConfig->setAdminMode(false);
            $this->setBody($oSmarty->fetch($this->_sSenedNowTemplate));
            $this->setAltBody($oSmarty->fetch($this->_sSenedNowTemplatePlain));
            $oConfig->setAdminMode(true);
            $oLang->setTplLanguage($iOldTplLang);
            $oLang->setBaseLanguage($iOldBaseLang);
            // set it back
            $oSmarty->security_settings['INCLUDE_ANY'] = $aStore['INCLUDE_ANY'];

            //Sets subject to email
            $this->setSubject( $oShop->oxshops__oxsendednowsubject->getRawValue() );
            $this->setReplyTo($oShop->oxshops__oxorderemail->value, $oShop->oxshops__oxname->getRawValue());

            return true;
        }

        return false;
    }


    /**
     * Sends reminder email to shop owner.
     *
     * @return bool
     */
    public function sendDummyStockReminder()
    {
        /** @var oxEmail $this */
        $oConfig      = $this->getConfig();
        /** @var oxArticleList $oArticleList */
        $oArticleList = oxNew("oxarticlelist");
        $oArticleList->loadIds( oxDb::getDb( true )->getCol( "SELECT `OXARTID` FROM `oxorderarticles` ORDER BY `OXTIMESTAMP` DESC LIMIT 5" ) );

        // nothing to remind?
        if ( $oArticleList->count() )
        {
            $oShop = $this->_getShop();

            //set mail params (from, fromName, smtp... )
            $this->_setMailParams($oShop);
            $oLang = oxRegistry::getLang();

            $oSmarty = $this->_getSmarty();
            $oConfig->setAdminMode( false );
            $this->setViewData("articles", $oArticleList);

            // Process view data array through oxOutput processor
            $this->_processViewArray();

            $this->setFrom($oShop->oxshops__oxowneremail->value, $oShop->oxshops__oxname->getRawValue());
            $this->setBody($oSmarty->fetch($this->getConfig()->getTemplatePath($this->_sReminderMailTemplate, false)));
            $this->setAltBody("");
            $this->setSubject($oLang->translateString('STOCK_LOW'));

            $oConfig->setAdminMode( true );

            return true;
        }

        return false;
    }


    /**
     * Sends a notification to the shop owner that price alarm was subscribed.
     * Returns true on success.
     *
     * @return bool
     */
    public function sendDummyPriceAlarmNotification()
    {
        /** @var oxEmail $this */
        $oConfig      = $this->getConfig();
        $this->_clearMailer();
        $oShop = $this->_getShop();

        //set mail params (from, fromName, smtp)
        $this->_setMailParams($oShop);

        $iLang = $this->getShop()->getLanguage();


        $oArticle = oxNew("oxarticle");
        //$oArticle->setSkipAbPrice( true );
        $oArticle->loadInLang($iLang, oxDb::getDb( true )->getOne( "SELECT `OXARTID` FROM `oxorderarticles` ORDER BY `OXTIMESTAMP` DESC LIMIT 1" ));
        $oLang = oxRegistry::getLang();

        // create messages
        $oSmarty = $this->_getSmarty();
        $oConfig->setAdminMode( false );

        $this->setViewData("product", $oArticle);
        $this->setViewData("email", $oShop->oxshops__oxowneremail->value);
        $this->setViewData("bidprice", $oLang->formatCurrency( 5 , $oCur));

        // Process view data array through oxOutput processor
        $this->_processViewArray();

        $this->setSubject( $oLang->translateString('PRICE_ALERT_FOR_PRODUCT', $iLang) . " " . $oArticle->oxarticles__oxtitle->getRawValue() );
        $this->setBody($oSmarty->fetch($this->_sOwnerPricealarmTemplate));
        $this->setFrom($oShop->oxshops__oxowneremail->value, $oShop->oxshops__oxname->getRawValue());
        $this->setReplyTo($oShop->oxshops__oxowneremail->value, $oShop->oxshops__oxname->getRawValue());

        $oConfig->setAdminMode( true );

        return true;
    }

    /**
     * Sets mailer additional settings and sends registration mail to user.
     * Returns true on success.
     *
     * @return bool
     */
    public function sendDummyRegisterEmail()
    {
        /** @var oxEmail $this */
        $oConfig = $this->getConfig();

        // add user defined stuff if there is any
        $oUser = $this->_addUserRegisterEmail( $oConfig->getUser() );

        // shop info
        $oShop = $this->_getShop();

        //set mail params (from, fromName, smtp )
        $this->_setMailParams($oShop);

        // create messages
        $oSmarty = $this->_getSmarty();

        $oConfig->setAdminMode( false );
        $this->setUser($oUser);

        // Process view data array through oxOutput processor
        $this->_processViewArray();

        $this->setBody($oSmarty->fetch($this->_sRegisterTemplate));
        $this->setAltBody($oSmarty->fetch($this->_sRegisterTemplatePlain));

        $this->setSubject( $oShop->oxshops__oxregistersubject->getRawValue() );
        $this->setReplyTo($oShop->oxshops__oxorderemail->value, $oShop->oxshops__oxname->getRawValue());

        $oConfig->setAdminMode( true );

        return true;
    }


    /**
     * Sets mailer additional settings and sends "SendDownloadLinks" mail to user.
     * Returns true on success.
     *
     * @return bool
     */
    public function sendDummyDownloadLinksMail()
    {
        /** @var oxEmail $this */
        $oConfig    = $this->getConfig();
        $oOrder     = oxNew( 'oxOrder' );
        $sOrderView = getViewName( 'oxorder' );

        $sOXID = oxDb::getDb( true )->getOne( "SELECT `OXID` FROM $sOrderView WHERE `OXSTORNO` = 0 ORDER BY `OXTIMESTAMP` DESC LIMIT 1" );

        if( $oOrder->load( $sOXID ) )
        {
            $iOrderLang = (int) (isset($oOrder->oxorder__oxlang->value) ? $oOrder->oxorder__oxlang->value : 0);

            // shop info
            $oShop = $this->_getShop($iOrderLang);

            //set mail params (from, fromName, smtp)
            $this->_setMailParams($oShop);

            //create messages
            $oLang = oxRegistry::getLang();
            $oSmarty = $this->_getSmarty();
            $this->setViewData("order", $oOrder);
            $this->setViewData("shopTemplateDir", $oConfig->getTemplateDir(false));

            $oUser = oxNew('oxuser');
            $this->setViewData("reviewuserhash", $oUser->getReviewUserHash($oOrder->oxorder__oxuserid->value));

            // Process view data array through oxoutput processor
            $this->_processViewArray();

            // #1469 - we need to patch security here as we do not use standard template dir, so smarty stops working
            $aStore['INCLUDE_ANY'] = $oSmarty->security_settings['INCLUDE_ANY'];
            //V send email in order language
            $iOldTplLang = $oLang->getTplLanguage();
            $iOldBaseLang = $oLang->getTplLanguage();
            $oLang->setTplLanguage($iOrderLang);
            $oLang->setBaseLanguage($iOrderLang);

            $oSmarty->security_settings['INCLUDE_ANY'] = true;
            // force non admin to get correct paths (tpl, img)
            $oConfig->setAdminMode(false);
            $this->setBody($oSmarty->fetch($this->_sSendDownloadsTemplate));
            $this->setAltBody($oSmarty->fetch($this->_sSendDownloadsTemplatePlain));
            $oConfig->setAdminMode(true);
            $oLang->setTplLanguage($iOldTplLang);
            $oLang->setBaseLanguage($iOldBaseLang);
            // set it back
            $oSmarty->security_settings['INCLUDE_ANY'] = $aStore['INCLUDE_ANY'];

            //Sets subject to email
            $this->setSubject( $oLang->translateString("DOWNLOAD_LINKS", null, false) );
            $this->setReplyTo($oShop->oxshops__oxorderemail->value, $oShop->oxshops__oxname->getRawValue());

            return true;
        }

        return false;
    }


    /**
     * Sets mailer additional settings and sends "SuggestMail" mail to user.
     * Returns true on success.
     *
     * @return bool
     */
    public function sendDummySuggestMail()
    {
        /** @var oxEmail $this */
        $oConfig             = $this->getConfig();
        $oUser               = $oConfig->getUser();
        $oUser->send_name    = $oUser->oxuser__oxfname->getRawValue() . " " . $oUser->oxuser__oxlname->getRawValue();
        $oUser->send_email   = $oUser->oxuser__oxusername->getRawValue();
        $oUser->rec_name     = $oUser->oxuser__oxfname->getRawValue() . " " . $oUser->oxuser__oxlname->getRawValue();
        $oUser->rec_email    = $oUser->oxuser__oxusername->getRawValue();
        $oUser->send_message = "Dies\r\nist\r\nmeine\r\nEmpfehlung\r\nan\r\ndich.";

        //sets language of shop
        $iCurrLang = $oConfig->getActiveShop()->getLanguage();

        $oProduct = oxNew("oxarticle");
        //$oArticle->setSkipAbPrice( true );
        $oProduct->load( oxDb::getDb( true )->getOne( "SELECT `OXARTID` FROM `oxorderarticles` ORDER BY `OXTIMESTAMP` DESC LIMIT 1" ) );

        // shop info
        $oShop = $this->_getShop($iCurrLang);
        $this->_setMailParams($oShop);

        // mailer stuff
        // send not pretending from suggesting user, as different email domain rise spam filters
        $this->setFrom($oShop->oxshops__oxorderemail->value, $oShop->oxshops__oxname->getRawValue());
        $this->setSMTP();

        // create messages
        $oSmarty = $this->_getSmarty();

        $this->setViewData( "product", $oProduct );
        $this->setUser($oUser);

        $this->setViewData("sArticleUrl", $oProduct->getLink());

        // Process view data array through oxOutput processor
        $this->_processViewArray();
        
        $oConfig->setAdminMode( false );

        $this->setBody($oSmarty->fetch($oConfig->getTemplatePath($this->_sSuggestTemplate,false)));
        $this->setAltBody($oSmarty->fetch($oConfig->getTemplatePath($this->_sSuggestTemplatePlain,false)));
        $this->setSubject( "Produktempfehlung" );

        $this->setReplyTo($oShop->oxshops__oxorderemail->value, $oShop->oxshops__oxname->getRawValue());

        $oConfig->setAdminMode(true);

        return true;
    }


    /**
     * Sets mailer additional settings and sends "WishlistMail" mail to user.
     * Returns true on success.
     *
     * @return bool
     */
    public function sendDummyWishlistMail()
    {
        $oConfig             = $this->getConfig();
        $oUser               = $oConfig->getUser();
        $oUser->send_id      = 0;
        $oUser->send_name    = $oUser->oxuser__oxfname->getRawValue() . " " . $oUser->oxuser__oxlname->getRawValue();
        $oUser->send_message = "Dies\r\nist\r\nmeine\r\nEmpfehlung\r\nan\r\ndich.";

        $this->_clearMailer();

        // shop info
        $oShop = $this->_getShop();

        // mailer stuff
        $this->setFrom($oShop->oxshops__oxorderemail->value, $oShop->oxshops__oxname->getRawValue());
        $this->setSMTP();

        // create messages
        $oSmarty = $this->_getSmarty();
        $oConfig->setAdminMode( false );
        $this->setUser($oUser);

        // Process view data array through oxoutput processor
        $this->_processViewArray();

        $this->setBody($oSmarty->fetch($oConfig->getTemplatePath($this->_sWishListTemplate,false)));
        $this->setAltBody($oSmarty->fetch($oConfig->getTemplatePath($this->_sWishListTemplatePlain,false)));
        $this->setSubject( "Mein Wunschzettel" );

        $this->setReplyTo($oShop->oxshops__oxorderemail->value, $oShop->oxshops__oxname->getRawValue());

        $oConfig->setAdminMode( true );

        return true;
    }
}
