<?php

namespace XD\Dashboard\Model;

use SilverStripe\Control\Controller;
use SilverStripe\Core\Convert;
use SilverStripe\Forms\CheckboxSetField;
use SilverStripe\Forms\DropdownField;
use SilverStripe\Forms\GridField\GridFieldDataColumns;
use SilverStripe\Forms\HeaderField;
use SilverStripe\Forms\OptionsetField;
use SilverStripe\ORM\ArrayList;
use SilverStripe\ORM\DataObject;
use SilverStripe\Reports\Report;
use SilverStripe\View\ArrayData;

class Panel extends DataObject
{
    private static $table_name = 'XDDashboard_Panel';

    private static $db = [
        'Title' => 'Varchar',
        'Sort' => 'Int',
        'GridSize' => 'Enum("Half,Full","Half")',
        'ReportClass' => 'Varchar',
        'ReportParameters' => 'Varchar',
        'ReportColumns' => 'Varchar',
        'Limit' => 'Int'
    ];

    private static $default_sort = 'Sort ASC';

    private static $has_one = [
        'Dashboard' => Dashboard::class
    ];

    private static $summary_fields = [
        'Title' => 'Title',
        'getReport.Title' => 'Report',
        'GridSize' => 'Panel size'
    ];

    public function getCMSFields()
    {
        $fields = parent::getCMSFields();
        $fields->removeByName(['ReportParameters', 'DashboardID', 'Sort', 'ReportColumns']);

        $fields->addFieldsToTab('Root.Main', [
            OptionsetField::create('GridSize', _t(__CLASS__ . '.PanelSize', 'Panel size'), $this->getGridSizes()),
            DropdownField::create(
                'ReportClass', 
                _t(__CLASS__ . '.Report', 'Report'),
                array_map(function(Report $report) {
                    return $report->getTitle();
                }, Report::get_reports())
            )
        ]);

        $report = $this->getReport();
        if ($report && $report->hasMethod('parameterFields') && $parameterFields = $report->parameterFields()) {
            $setParameters = $this->getParameters();
            
            /** @var FormField $field */
            $fields->push(HeaderField::create(_t(__CLASS__ . '.ReportParameters', 'Report Parameters')));
            foreach ($parameterFields as $field) {
                $fieldName = $field->getName();
                // Check if a paramer is set
                if (isset($setParameters[$fieldName])) {
                    $field->setValue($setParameters[$fieldName]);
                }
                // Namespace fields for easier handling in form submissions
                $field->setName(sprintf('Parameters[%s]', $fieldName));
                $field->addExtraClass('no-change-track'); // ignore in changetracker
                $fields->push($field);
            }
        }

        if ($report && $columns = $report->columns()) {
            $columns = array_map(function($fieldConfig) {
                return is_array($fieldConfig) ? $fieldConfig['title'] : $fieldConfig;
            }, $columns);
            $fields->push(CheckboxSetField::create(
                'ReportColumns', 
                _t(__CLASS__ . '.ShowColumns', 'Show columns'),
                $columns
            ));
        }

        return $fields;
    }

    protected function onBeforeWrite()
    {
        parent::onBeforeWrite();
        if ($parameters = Controller::curr()->getRequest()->postVar('Parameters')) {
            $this->setParameters($parameters);
        }
    }

    /**
     * @return Report
     */
    public function getReport()
    {
        if ($this->ReportClass) {
            return singleton($this->ReportClass);
        }

        return null;
    }

    public function setParameters($parameters)
    {
        if (is_array($parameters)) {
            $parameters = json_encode($parameters);
        }

        $this->ReportParameters = $parameters;
    }

    public function getParameters()
    {
        $parameters = $this->ReportParameters ?? '';
        $parametersArr = json_decode($parameters, true) ?? [];
        return array_filter($parametersArr);
    }

    public function getColumns()
    {
        $showColumns = json_decode($this->ReportColumns, true);
        if ($showColumns && $report = $this->getReport()) {
            $columns = new ArrayList();
            foreach ($report->columns() as $field => $fieldConfig) {
                if (in_array($field, $showColumns) != false) {
                    if (!is_array($fieldConfig)) {
                        $title = $fieldConfig;
                    } else {
                        $title = $fieldConfig['title'];
                    }

                    $columns->push(new ArrayData([
                        'Field' => $field,
                        'Title' => $title,
                    ]));
                }
            }
            return $columns;
        }

        return [];
    }

    public function getReportData()
    {
        if ($report = $this->getReport()) {

            $showColumns = json_decode($this->ReportColumns);
            $records = $report->records($this->getParameters());
            if ($this->Limit && method_exists($records, 'limit')) {
                $records = $records->limit($this->Limit);
            }

            // Create a Gridfield for column retrieval
            $gridField = $report->getReportField();
            $gridFieldConfig = $gridField->getConfig();
            $columns = $gridFieldConfig->getComponentByType(new GridFieldDataColumns());
            
            $data = new ArrayList();
            foreach ($records as $record) {
                
                $columnsList = new ArrayList();
                foreach ($showColumns as $field) {
                    
                    // Convert the attributes to a html string
                    $htmlAttributes = '';
                    foreach($columns->getColumnAttributes($gridField, $record, $field) as $attributeKey => $attributeValue) {
                        $htmlAttributes .= sprintf(
                            ' %s="%s"',
                            $attributeKey,
                            Convert::raw2att($attributeValue)
                        );
                    }

                    $columnsList->push(new ArrayData([
                        'Value' => $columns->getColumnContent($gridField, $record, $field),
                        'Attributes' => $htmlAttributes
                    ]));
                }

                $row = [
                    'Columns' => $columnsList
                ];

                if ($record->hasMethod('CMSEditLink')) {
                    $row['Link'] = $record->CMSEditLink();
                }

                $data->push(new ArrayData($row));
            }
            
            return $data;
        }

        return [];
    }

    public function getGridSizes()
    {
        return array_map(function($size) {
            return _t(__CLASS__ . ".GridSize_$size", $size);
        }, $this->dbObject('GridSize')->enumValues());
    }

    public function forTemplate()
    {
        return $this->renderWith($this->ClassName);
    }
}
