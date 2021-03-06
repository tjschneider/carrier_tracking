<?php
/**
 * carrier_tacking / parcel service / Paketdienste
 *
 * This file is part of the module carrier_tacking for OXID eShop Community Edition.
 *
 * The module carrier_tacking for OXID eShop Community Edition is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * The module carrier_tacking for OXID eShop Community Edition is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with OXID eShop Community Edition. If not, see <http://www.gnu.org/licenses/>.
 *
 * @link https://github.com/OXIDprojects/carrier_tracking
 * @package admin
 */

/**
 * Admin carrier details manager.
 * Creates form for submitting new admin links or modifying old ones.
 * Admin Menu: Customer News -> Links.
 * @package admin
 */
class Carrier_Main extends oxAdminDetails
{
    /**
     * Sets carrier information data (or leaves empty), returns name of template
     * file "carrier_main.tpl".
     * @return string
     */
    public function render()
    {
        parent::render();

        $soxId = $this->_aViewData["oxid"] = $this->getEditObjectId();
        if ( $soxId != "-1" && isset( $soxId)) {
            /** @var oxcarrier $oCarrier */
            $oCarrier = oxNew("oxcarrier");
            $oCarrier->loadInLang( $this->_iEditLang, $soxId );

            $oOtherLang = $oCarrier->getAvailableInLangs();
            if (!isset($oOtherLang[$this->_iEditLang])) {
                // echo "language entry doesn't exist! using: ".key($oOtherLang);
                $oCarrier->loadInLang( key($oOtherLang), $soxId );
            }
            $this->_aViewData["edit"] =  $oCarrier;

            // remove already created languages
            $this->_aViewData["posslang"] =  array_diff (oxRegistry::getLang()->getLanguageNames(), $oOtherLang);

            foreach ( $oOtherLang as $id => $language) {
                $oLang= new oxStdClass();
                $oLang->sLangDesc = $language;
                $oLang->selected = ($id == $this->_iEditLang);
                $this->_aViewData["otherlang"][$id] =  clone $oLang;
            }
        }

        return "carrier_main.tpl";
    }

    /**
     * Saves information about link (active, date, URL, description, etc.) to DB.
     *
     * @return mixed
     */
    public function save()
    {
        parent::save();
        $soxId      = oxRegistry::getConfig()->getRequestParameter("oxid");
        $aParams    = oxRegistry::getConfig()->getRequestParameter("editval");

        // checkbox handling
        if (!isset($aParams['oxcarriers__oxactive'])) {
            $aParams['oxcarriers__oxactive'] = 0;
        }

        // adds space to the end of URL description to keep new added links visible
        // if URL description left empty
        if (isset($aParams['oxcarriers__oxshortdesc']) && strlen(
        $aParams['oxoxcarriers__oxshortdesc']
        ) == 0
        ) {
            $aParams['oxcarriers__oxshortdesc'] .= " ";
        }

        // shopid
        $sShopID = oxRegistry::getConfig()->getShopId();
        $aParams['oxcarriers__oxshopid'] = $sShopID;

        /** @var oxcarrier $oCarrier */
        $oCarrier = oxNew("oxcarrier");

        if ( $soxId != "-1") {
            //$oCarrier->load( $soxId );
            $oCarrier->loadInLang( $this->_iEditLang, $soxId );

        } else {
            $aParams['oxcarriers__oxid'] = null;
        }

        $oCarrier->setLanguage(0);
        $oCarrier->assign( $aParams);
        $oCarrier->setLanguage( $this->_iEditLang );
        $oCarrier = oxRegistry::get("oxUtilsFile")->processFiles( $oCarrier );
        $oCarrier->save();

        // set oxid if inserted
        $this->setEditObjectId( $oCarrier->getId() );
    }

    /**
     * Saves link description in different languages (eg. english).
     *
     * @return null
     */
    public function saveinnlang()
    {
        $soxId      = oxRegistry::getConfig()->getRequestParameter( "oxid");
        $aParams    = oxRegistry::getConfig()->getRequestParameter( "editval");
        // checkbox handling
        if (!isset($aParams['oxcarriers__oxactive'])) {
            $aParams['oxcarriers__oxactive'] = 0;
        }

        // shopid
        $sShopID = oxRegistry::getConfig()->getShopId();
        $aParams['oxcarriers__oxshopid'] = $sShopID;

        /** @var oxcarrier $oCarrier */
        $oCarrier = oxNew("oxcarrier");

        if ($soxId != "-1") {
            $oCarrier->loadInLang($this->_iEditLang, $soxId);
        } else {
            $aParams['oxcarriers__oxid'] = null;
        }

        $oCarrier->setLanguage(0);
        $oCarrier->assign( $aParams);

        // apply new language
        $oCarrier->setLanguage( $this->_iEditLang);
        $oCarrier = oxRegistry::get("oxUtilsFile")->processFiles( $oCarrier );
        $oCarrier->save();

        // set oxid if inserted
        $this->setEditObjectId( $oCarrier->getId() );
    }

    /**
     * Deletes selected master picture.
     *
     * @return null
     */
    public function deletePicture()
    {
        $myConfig = $this->getConfig();

        if ( $myConfig->isDemoShop() ) {
            $oEx = new oxExceptionToDisplay();
            $oEx->setMessage('CARRIER_PICTURES_UPLOADISDISABLED');
            oxRegistry::get("oxUtilsView")->addErrorToDisplay( $oEx, false );
            return;
        }

        $sOxId = $this->getEditObjectId();
        $sField = oxRegistry::getConfig()->getRequestParameter('masterPicField');
        if (empty($sField)) {
            return;
        }

        /** @var oxcarrier $oCarrier */
        $oCarrier = oxNew("oxcarrier");
        $oCarrier->load($sOxId);
        $this->_deleteCatPicture($oCarrier, $sField);
    }

    /**
     * Delete carrier picture, specified in $sField parameter
     *
     * @param oxcarrier $oItem  active carrier object
     * @param string     $sField picture field name
     *
     * @return null
     */
    protected function _deleteCatPicture(oxcarrier $oItem, $sField)
    {
        $myConfig = $this->getConfig();
        $sItemKey = 'oxcarriers__'.$sField;

        switch ($sField) {
            case 'oxicon':
                $sImgType = 'CARICO';
                break;

            default:
                $sImgType = false;
        }

        if ($sImgType !== false) {

            $myUtilsPic = oxRegistry::get("oxUtilsPic");
            $sDir = $myConfig->getPictureDir(false);
            $myUtilsPic->safePictureDelete($oItem->$sItemKey->value, $sDir . oxRegistry::get("oxUtilsFile")->getImageDirByType($sImgType), 'oxcarriers', $sField);

            $oItem->$sItemKey = new oxField();
            $oItem->save();
        }
    }
}
