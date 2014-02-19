<?php
/**
 * Created By: Levi
 * Date: 6/27/13
 */

chdir('../../');
require("app/framework.php");

if (!Request::get('json')) {
    die("usage: ?json=filename");
}


$json = 'http://bort/meta/'.Request::get('json');
$importData = json_decode(file_get_contents($json), true);

$db = App::getDBO();

foreach ($importData as $typeName => $typeGroup) {

    $types = FieldType::getByFields(array('typeName'=>$typeName));
    if (!empty($types)) {
        $db->query("DELETE FROM FieldValues WHERE typeId=".$types[0]->id);
        //$db->query("DELETE FROM FieldTypes WHERE id=".$types[0]->id);
        /** @var FieldType $type */
        $type = $types[0];
        $type->typeName = $typeName;
        $type->save();

    } else {
        $type = new FieldType();
        $type->typeName = $typeName;
        $type->isFreeText = false;
        $type->save();
    }


    $i = 0;
    foreach($typeGroup as $key => $value) {
        if (!$key || is_int($key))
            $key = $value;
        $field = new FieldValue();
        $field->typeId = $type->id;
        $field->value = $key;
        $field->order = ($i++);
        $field->isOther = false;
        $field->hidden = false;
        $field->save();

        if ($key != $value && is_array($value)) {
            foreach ($value as $item) {
                $subField = new FieldValue();
                $subField->typeId = $type->id;
                $subField->value = $item;
                $subField->order = ($i++);
                $subField->isOther = false;
                $subField->hidden = false;
                $subField->parentId = $field->id;
                $subField->save();
            }
        }
    }
}

Log::displayFullLog();