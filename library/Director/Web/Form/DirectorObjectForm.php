<?php

namespace Icinga\Module\Director\Web\Form;

abstract class DirectorObjectForm extends QuickForm
{
    protected $db;

    protected $object;

    private $objectName;

    private $className;

    public function onSuccess()
    {
        $values = $this->getValues();

        if ($this->object->supportsGroups()) {
            unset($values['groups']);
        }

        if ($this->object) {
            $this->object->setProperties($values)->store();
            $this->storeGroupMembership();
            $this->redirectOnSuccess(
                sprintf(
                    $this->translate('The Icinga %s has successfully been stored'),
                    $this->translate($this->getObjectName())
                )
            );
        } else {
            $class = $this->getObjectClassname();
            $this->object = $class::create($values)->store($this->db);
            $this->storeGroupMembership();
            $this->redirectOnSuccess(
                sprintf(
                    $this->translate('A new Icinga %s has successfully been created'),
                    $this->translate($this->getObjectName())
                )
            );
        }
    }

    protected function storeGroupMembership()
    {
        if (! $this->object->supportsGroups()) {
            return;
        }

        $this->object->groups()->set(
            preg_split('/\s*,\s*/', $this->getValue('groups'), -1, PREG_SPLIT_NO_EMPTY)
        )->store();
    }

    protected function optionalEnum($enum)
    {
        return array(
            null => $this->translate('- please choose -')
        ) + $enum;
    }

    protected function optionalBoolean($key, $label, $description)
    {
        return $this->addElement('select', $key, array(
            'label' => $label,
            'description' => $description,
            'multiOptions' => $this->selectBoolean()
        ));
    }

    protected function selectBoolean()
    {
        return array(
            null => $this->translate('- not set -'),
            'y'  => $this->translate('Yes'),
            'n'  => $this->translate('No'),
        );
    }

    public function hasElement($name)
    {
        return $this->getElement($name) !== null;
    }

    public function getObject()
    {
        return $this->object;
    }

    protected function getObjectClassname()
    {
        if ($this->className === null) {
            return 'Icinga\\Module\\Director\\Objects\\'
               . substr(join('', array_slice(explode('\\', get_class($this)), -1)), 0, -4);
        }

        return $this->className;
    }

    protected function getObjectname()
    {
        if ($this->objectName === null) {
            return substr(join('', array_slice(explode('\\', get_class($this)), -1)), 6, -4);
        }

        return $this->objectName;
    }

    public function loadObject($id)
    {
        $class = $this->getObjectClassname();
        $this->object = $class::load($id, $this->db);
        if (! is_array($id)) {
            $this->addHidden('id');
        }
        $this->setDefaults($this->object->getProperties());

        if ($this->object->supportsCustomVars()) {
            foreach ($this->object->vars() as $key => $value) {
                $this->addCustomVar($key, $value);
            }
        }

        if (! $this->hasBeenSubmitted()) {
            $this->beforeValidation($this->object->getProperties());
        }
        return $this;
    }

    protected function addCustomVar($key, $var)
    {
        $this->addElement('text', 'var_' . $key, array(
            'label' => 'vars.' . $key,
            'value' => $var->getValue()
        ));
    }

    public function setDb($db)
    {
        $this->db = $db;
        if ($this->hasElement('parent_zone_id')) {
            $this->getElement('parent_zone_id')
                ->setMultiOptions($this->optionalEnum($db->enumZones()));
        }
        if ($this->hasElement('host_id')) {
            $this->getElement('host_id')
                ->setMultiOptions($this->optionalEnum($db->enumHosts()));
        }
        if ($this->hasElement('hostgroup_id')) {
            $this->getElement('hostgroup_id')
                ->setMultiOptions($this->optionalEnum($db->enumHostgroups()));
        }
        if ($this->hasElement('service_id')) {
            $this->getElement('service_id')
                ->setMultiOptions($this->optionalEnum($db->enumServices()));
        }
        if ($this->hasElement('servicegroup_id')) {
            $this->getElement('servicegroup_id')
                ->setMultiOptions($this->optionalEnum($db->enumServicegroups()));
        }
        if ($this->hasElement('user_id')) {
            $this->getElement('user_id')
                ->setMultiOptions($this->optionalEnum($db->enumUsers()));
        }
        if ($this->hasElement('usergroup_id')) {
            $this->getElement('usergroup_id')
                ->setMultiOptions($this->optionalEnum($db->enumUsergroups()));
        }
        if ($this->hasElement('zone_id')) {
            $this->getElement('zone_id')
                ->setMultiOptions($this->optionalEnum($db->enumZones()));
        }
        if ($this->hasElement('check_command_id')) {
            $this->getElement('check_command_id')
                ->setMultiOptions($this->optionalEnum($db->enumCheckCommands()));
        }
        if ($this->hasElement('command_id')) {
            $this->getElement('command_id')
                ->setMultiOptions($this->optionalEnum($db->enumCommands()));
        }
        return $this;
    }

    private function dummyForTranslation()
    {
        $this->translate('Host');
        $this->translate('Service');
        $this->translate('Zone');
        $this->translate('Command');
        // ... TBC
    }
}
