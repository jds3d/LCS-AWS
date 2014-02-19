<?php
/**
 * Created By: Levi
 * Date: 7/7/13
 */
class ContactsModule extends DataObjectModule {

    public function __construct() {
        // data object name
        parent::__construct('ContactRecord');
    }

    public function getRequiredPermissions($method, $input) {
        // TODO: add permissions as needed
        return array();
    }

    protected function retrieveDataObjects($params) {
        // TODO: add filtering as needed
        return parent::retrieveDataObjects($params);
    }

    protected function getStructureDefinition() {
        return parent::getStructureDefinition();
    }

    protected function isValid($object) {
        // TODO: add validation as needed
        return true;
    }

    protected function sanitize($object) {
        // TODO: add sanitization as needed
        return $object;
    }

    public function action_newoutreach() {
        // get an array of flat fields
        $fields = Request::get("flatFieldNames");
        $flatArray = array();
        foreach ($fields as &$fieldName) {
            // get the field
            $flatArray[$fieldName] = Request::get($fieldName);
        }

        $flatArray['recordUser'] = 1;


        $complexFields = Request::get("complexFieldNames");
        $complexArray = array();

        foreach ($complexFields as &$fieldName) {
            $complexArray[$fieldName] = Request::get($fieldName);
        }

        return ContactRecord::createOutreachContact($flatArray,$complexArray);
    }



}