<?php

namespace XD\Dashboard\Model;

use SilverStripe\Forms\GridField\GridField;
use SilverStripe\Forms\GridField\GridFieldAddNewButton;
use SilverStripe\Forms\GridField\GridFieldConfig_RecordEditor;
use SilverStripe\Forms\TextField;
use SilverStripe\ORM\DataObject;
use SilverStripe\Security\Group;
use SilverStripe\Security\Security;
use SilverStripe\TagField\TagField;
use Symbiote\GridFieldExtensions\GridFieldAddNewMultiClass;
use Symbiote\GridFieldExtensions\GridFieldOrderableRows;

class Dashboard extends DataObject
{
    private static $table_name = 'XDDashboard_Dashboard';

    private static $db = [
        'Title' => 'Varchar'
    ];

    private static $has_many = [
        'Panels' => Panel::class
    ];

    private static $many_many = [
        'Groups' => Group::class
    ];

    private static $summary_fields = [
        'Title' => 'Title',
        'Panels.Count' => 'Panels',
        'GroupNames' => 'Available for',
    ];

    public function getCMSFields()
    {
        $fields = parent::getCMSFields();
        $fields->removeByName(['Panels', 'Groups']);
        $gridFieldConfig = GridFieldConfig_RecordEditor::create();
        $gridFieldConfig->removeComponentsByType(new GridFieldAddNewButton());
        $gridFieldConfig->addComponent(new GridFieldOrderableRows());
        $gridFieldConfig->addComponent(new GridFieldAddNewMultiClass());
        $fields->addFieldsToTab('Root.Main', [
            TextField::create('Title', _t(__CLASS__ . '.Title', 'Title')),
            GridField::create('Panels', _t(__CLASS__ . '.Panels', 'Panels'), $this->Panels(), $gridFieldConfig),
            TagField::create('Groups', _t(__CLASS__ . '.RestrictToGroups', 'Restrict to groups'), Group::get(), $this->Groups()),
        ]);

        return $fields;
    }

    public function getGroupNames()
    {
        $groups = $this->Groups()->column('Title');
        if (count($groups)) {
            return implode(', ', $this->Groups()->column('Title'));
        }

        return _t(__CLASS__ . '.Everyone', 'Everyone');
    }

    public function forTemplate()
    {
        return $this->renderWith(__CLASS__);
    }

    /**
     * Return all dashboards the current user can view
     */
    public static function getForCurrentUser()
    {
        $member = Security::getCurrentUser();
        return Dashboard::get()->filterByCallback(function(Dashboard $dashboard) use ($member) {
            return $dashboard->canView($member);
        });
    }

    public function canView($member = null)
    {
        if (!$member) {
            $member = Security::getCurrentUser();
        }
        
        if (($groups = $this->Groups()) && $groups->count()) {
            return $member->inGroups($groups);
        }

        // TODO: default true or logged in only ?
        return true;
    }
}
