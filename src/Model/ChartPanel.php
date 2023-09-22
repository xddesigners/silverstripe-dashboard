<?php

namespace XD\Dashboard\Model;

use SilverStripe\Forms\DropdownField;
use SilverStripe\Forms\OptionsetField;
use SilverStripe\Forms\SelectField;
use XD\Charts\Charts\Chart;
use XD\Charts\Charts\DataSet;

class ChartPanel extends Panel
{
    private static $table_name = 'XDDashboard_ChartPanel';

    private static $db = [
        'ChartType' => 'Varchar',
        'XScaleColumn' => 'Varchar',
        'YScaleColumn' => 'Varchar',
        'StackOnColumn' => 'Varchar',
    ];

    private static $chart_types = [
        'line',
        'bar'
    ];

    private static $chart_point_background_color = 'rgba(67, 83, 109, .5)';

    private static $chart_background_colors = [
        'rgba(0, 90, 147, .8)',
        'rgba(0, 113, 196, .8)',
        'rgba(102, 16, 242, .8)',
        'rgba(111, 66, 193, .8)',
        'rgba(232, 62, 140, .8)',
        'rgba(218, 39, 59, .8)',
        'rgba(253, 126, 20, .8)',
        'rgba(255, 193, 7, .8)',
        'rgba(0, 138, 0, .8)',
        'rgba(32, 201, 151, .8)',
        'rgba(41, 171, 226, .8)',
    ];

    public function getCMSFields()
    {
        $fields = parent::getCMSFields();
        $fields->removeByName(['XScaleColumn', 'YScaleColumn', 'StackOnColumn', 'ReportColumns']);

        $types = self::config()->get('chart_types');
        $fields->push(DropdownField::create(
            'ChartType', 
            _t(__CLASS__ . '.ChartType', 'Chart type'), 
            array_combine($types, $types)
        ));

        $report = $this->getReport();
        if ($report && $columns = $report->columns()) {
            $columns = array_map(function($fieldConfig) {
                return is_array($fieldConfig) ? $fieldConfig['title'] : $fieldConfig;
            }, $columns);

            $fields->push(OptionsetField::create(
                'XScaleColumn', 
                _t(__CLASS__ . '.XScaleColumn', 'X-as'),
                $columns
            ));

            $fields->push(OptionsetField::create(
                'YScaleColumn', 
                _t(__CLASS__ . '.YScaleColumn', 'Y-as'),
                $columns
            ));
        }
        
        if ($report && $parameterFields = $report->parameterFields()) {
            $options = [];
            // Only user parameters that have set options as split possibilities
            foreach ($parameterFields as $field) {
                if ($field instanceof SelectField) {
                    $fieldName = $field->getName();
                    $options[$fieldName] = $field->Title();
                }
            }

            if (count($options)) {
                $options[false] = _t(__CLASS__ . 'StackOnColumnNone', 'Niet splitsen');
                $fields->push(OptionsetField::create(
                    'StackOnColumn', 
                    _t(__CLASS__ . '.StackOnColumn', 'Splits data op'),
                    $options
                ));
            }
        }

        return $fields;
    }

    public function onBeforeWrite()
    {
        parent::onBeforeWrite();
        $this->ReportColumns = json_encode([
            $this->XScaleColumn,
            $this->YScaleColumn,
        ]);
    }

    public function getChart()
    {
        $report = $this->getReport();
        if (!$report) {
            return null;
        }

        $chart = new Chart();
        $config = $chart->getConfig();
        $config->setType($this->ChartType);
        
        $config->setLegendPosition('right');
        $config->setLegendLabelSize(15, 15);
        $config->setPadding(0);

        // Stacked line chart
        if ($this->StackOnColumn && $this->ChartType != 'bar') {
            $config->setOption('scales.y.stacked', true);
        }
        
        $data = $config->getData();

        $parameters = $this->getParameters();
        
        $allRecords = $report->records($parameters);
        if ($this->Limit && method_exists($allRecords, 'limit')) {
            $allRecords = $allRecords->limit($this->Limit);
        }

        $labelCol = $allRecords->column($this->XScaleColumn);
        $data->setLabels(array_values($labelCol));

        $pointColor = self::config()->get('chart_point_background_color');
        
        if ($this->StackOnColumn) {
            $fields = $this->getReport()->parameterFields();
            $field = $fields->fieldByName($this->StackOnColumn);

            $i = 0;
            foreach ($field->getSource() as $field => $label) {
                $splitParams = $parameters;
                $splitParams[$this->StackOnColumn] = $field;
                $records = $report->records($splitParams);
                if ($this->Limit && method_exists($records, 'limit')) {
                    $records = $records->limit($this->Limit);
                }

                $dataSet = new DataSet();
                $dataSet->setLabel($label);
                $dataSet->setData($records->column($this->YScaleColumn));
                $dataSet->setOption('fill', true);
                $dataSet->setOption('pointRadius', 4);
                $dataSet->setOption('backgroundColor', self::getBackgroundColor($i));
                $dataSet->setOption('pointBackgroundColor', $pointColor);
                $data->addDataSet($dataSet);
                $i++;
            }
        } else {
            $dataSet = new DataSet();
            $dataSet->setLabel($this->Title);
            $dataSet->setData($allRecords->column($this->YScaleColumn));
            $dataSet->setOption('fill', true);
            $dataSet->setOption('pointRadius', 4);
            $dataSet->setOption('backgroundColor', self::getBackgroundColor(0));
            $dataSet->setOption('pointBackgroundColor', $pointColor);
            $data->addDataSet($dataSet);
        }

        return $chart;
    }

    public static function getBackgroundColor($index)
    {
        $backgroundColors = self::config()->get('chart_background_colors');
        $availableLength = count($backgroundColors);

        while ($index >= $availableLength) {
            $index -= $availableLength;
        }

        return $backgroundColors[$index];
    }

    public function createDataSet($label, $records)
    {
        $dataSet = new DataSet();
        $dataSet->setLabel($label);
        $dataSet->setData($records->column($this->YScaleColumn));
        $dataSet->setOption('fill', true);
        $dataSet->setOption('pointRadius', 4);
        $dataSet->setOption('backgroundColor', self::config()->get('chart_background_colors'));
        $dataSet->setOption('pointBackgroundColor', self::config()->get('chart_point_background_color'));

        return $dataSet;
    }
}
