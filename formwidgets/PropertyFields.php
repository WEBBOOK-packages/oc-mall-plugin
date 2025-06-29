<?php

declare(strict_types=1);

namespace WebBook\Mall\FormWidgets;

use Backend\Classes\FormField;
use Backend\Classes\FormWidgetBase;
use Backend\FormWidgets\ColorPicker;
use Backend\FormWidgets\DatePicker;
use Backend\FormWidgets\FileUpload;
use Backend\FormWidgets\RichEditor;
use WebBook\Mall\Models\Property;
use WebBook\Mall\Models\PropertyGroup;
use WebBook\Mall\Models\PropertyValue;

/**
 * PropertyFields Form Widget
 */
class PropertyFields extends FormWidgetBase
{
    /**
     * {@inheritDoc}
     */
    protected $defaultAlias = 'propertyfields';

    /**
     * {@inheritDoc}
     */
    public function render()
    {
        $this->prepareVars();

        return $this->makePartial('propertyfields');
    }

    /**
     * Prepares the form widget view data
     */
    public function prepareVars()
    {
        $this->vars['name']   = $this->formField->getName();
        $this->vars['values'] = $this->model->property_values ?? collect([]);
        $this->vars['model']  = $this->model;

        $groups = optional(
            $this->controller->vars['formModel']
                ->categories
                ->flatMap->inherited_property_groups
        )->unique('id');

        if ($this->controller->vars['formModel']->inventory_management_method !== 'single') {
            $useForVariants = $this->useVariantSpecificPropertiesOnly();
            $groups         = PropertyGroup::with([
                'properties' => function ($q) use ($useForVariants) {
                    $q->wherePivot('use_for_variants', $useForVariants);
                },
            ])->whereIn('id', $groups->pluck('id'))->get();
        }

        // Make sure every property is only displayed once even if it is
        // assigned to more than one property group.
        $knownIds = collect([]);
        $groups   = $groups->map(function (PropertyGroup $group) use (&$knownIds) {
            $unknownIds = $group->properties->pluck('id')->diff($knownIds);
            $knownIds   = $knownIds->concat($unknownIds);

            $properties = $group->properties->filter(fn ($property) => $unknownIds->contains($property->id));

            $group->setRelation('properties', $properties->sortBy('sort_order'));

            return $group;
        });

        $this->vars['groups'] = $groups->sortBy('pivot.sort_order');
    }

    public function createFormWidget(Property $property, ?PropertyValue $value)
    {
        if (! $value) {
            $value = new PropertyValue();
        }

        switch ($property->type) {
            case 'color':
                return $this->color($property, $value);
            case 'textarea':
                return $this->textarea($property, $value);
            case 'dropdown':
                return $this->dropdown($property, $value);
            case 'checkbox':
                return $this->checkbox($property, $value);
            case 'richeditor':
                return $this->richeditor($property, $value);
            case 'image':
                return $this->image($property, $value);
            case 'switch':
                return $this->switch($property, $value);
            case 'date':
                return $this->datepicker($property, $value);
            case 'datetime':
                return $this->datetimepicker($property, $value);
            case 'float':
            case 'integer':
                return $this->textfield($property, $value, 'number');
            default:
                return $this->textfield($property, $value);
        }
    }

    public function fieldPrefix(): string
    {
        return $this->formField->config['fieldPrefix'] ?? 'PropertyValues';
    }

    protected function newFormField($property, $subkey = null): FormField
    {
        $subkey    = $subkey ? '[' . $subkey . ']' : '';
        $fieldName = sprintf('%s[%s]%s', $this->fieldPrefix(), $property->id, $subkey);

        return new FormField($fieldName, $property->name);
    }

    protected function useVariantSpecificPropertiesOnly(): bool
    {
        return isset($this->formField->config['variantPropertiesOnly']) && $this->formField->config['variantPropertiesOnly'] === true;
    }

    protected function backendPartial(string $partial)
    {
        // October 2.0, add _ prefix.
        if (class_exists('System')) {
            $partial = '_' . $partial;
        }

        $path = sprintf('~/modules/backend/widgets/form/partials/%s', $partial);

        // October 2.0, add .htm extension.
        if (class_exists('System')) {
            $path .= '.htm';
        }

        return $path;
    }

    private function color($property, PropertyValue $value)
    {
        $config = $this->makeConfig([
            'model' => $value,
        ]);

        $colorFormField        = $this->newFormField($property, 'hex');
        $colorFormField->value = $value->value['hex'] ?? '';

        $textFormField            = $this->newFormField($property, 'name');
        $textFormField->value     = $value->value['name'] ?? '';
        $textFormField->valueFrom = 'value';

        $colorWidget             = new ColorPicker($this->controller, $colorFormField, $config);
        $colorWidget->allowEmpty = true;
        $colorWidget->bindToController();

        return $this->makePartial(
            'colorpicker',
            ['field' => $property, 'colorWidget' => $colorWidget, 'textFormField' => $textFormField]
        );
    }

    private function textfield($property, PropertyValue $value, $type = 'text')
    {
        return $this->makePartial('textfield', ['field' => $property, 'value' => $value, 'type' => $type]);
    }

    private function textarea($property, PropertyValue $value)
    {
        $config = $this->makeConfig([
            'model' => $value,
        ]);

        $formField            = $this->newFormField($property);
        $formField->value     = $value->value;
        $formField->valueFrom = 'value';

        return $this->makePartial(
            'textarea',
            ['field' => $property, 'value' => $value->value]
        );
    }

    private function richeditor($property, PropertyValue $value)
    {
        $config = $this->makeConfig([
            'model' => $value,
        ]);

        $formField            = $this->newFormField($property);
        $formField->value     = $value->value;
        $formField->valueFrom = 'value';

        $widget             = new RichEditor($this->controller, $formField, $config);
        $widget->allowEmpty = true;
        $widget->bindToController();

        return $this->makePartial(
            'richeditor',
            ['field' => $property, 'widget' => $widget, 'value' => $value->value]
        );
    }

    private function datetimepicker($property, PropertyValue $value)
    {
        $config = $this->makeConfig([
            'model' => $value,
        ]);

        $formField            = $this->newFormField($property);
        $formField->value     = $value->value;
        $formField->valueFrom = 'value';

        $widget             = new DatePicker($this->controller, $formField, $config);
        $widget->allowEmpty = true;
        $widget->ignoreTimezone = true;
        $widget->bindToController();

        return $this->makePartial(
            'datetimepicker',
            ['field' => $property, 'widget' => $widget, 'value' => $value]
        );
    }

    private function datepicker($property, PropertyValue $value)
    {
        $config = $this->makeConfig([
            'model' => $value,
        ]);

        $formField            = $this->newFormField($property);
        $formField->value     = $value->value;
        $formField->valueFrom = 'value';

        $widget             = new DatePicker($this->controller, $formField, $config);
        $widget->allowEmpty = true;
        $widget->ignoreTimezone = true;
        $widget->mode = 'date';
        $widget->bindToController();

        return $this->makePartial(
            'datepicker',
            ['field' => $property, 'widget' => $widget, 'value' => $value]
        );
    }

    private function dropdown($property, PropertyValue $value)
    {
        $escapedValue = e($value->value);

        $formField          = $this->newFormField($property);
        $formField->value   = $escapedValue;
        $formField->label   = $property->name;
        $formField->options = collect($property->options)->mapWithKeys(function ($i) {
            $value = e($i['value']);

            return [$value => $value];
        })->toArray();

        $widget = $this->makePartial(
            $this->backendPartial('field_dropdown'),
            ['field' => $formField, 'value' => $escapedValue]
        );

        return $this->makePartial('dropdown', ['widget' => $widget, 'field' => $property]);
    }

    private function checkbox($property, PropertyValue $value)
    {
        $formField          = $this->newFormField($property);
        $formField->value   = $value->value;
        $formField->label   = $property->name;
        $formField->options = collect($property->options)->map(fn ($i) => [$i['value'], $i['value']])->toArray();

        return $this->makePartial(
            $this->backendPartial('field_checkbox'),
            ['field' => $formField, 'value' => $value->value]
        );
    }

    private function switch($property, PropertyValue $value)
    {
        $formField          = $this->newFormField($property);
        $formField->value   = $value->value;
        $formField->label   = $property->name;
        $formField->options = collect($property->options)->map(fn ($i) => [$i['value'], $i['value']])->toArray();

        return $this->makePartial(
            $this->backendPartial('field_switch'),
            ['field' => $formField, 'value' => $value->value]
        );
    }

    private function image($property, PropertyValue $value)
    {
        $config = $this->makeConfig([
            'model'      => optional($this->model->property_values->where('property_id', $property->id))
                ->first() ?? new PropertyValue(),
            'sessionKey' => $this->sessionKey,
        ]);

        $formField            = $this->newFormField($property);
        $formField->valueFrom = 'image';

        $widget        = new FileUpload($this->controller, $formField, $config);
        $widget->alias = 'image';
        $widget->bindToController();

        return $this->makePartial(
            'fileupload',
            ['field' => $property, 'widget' => $widget, 'value' => $value->value, 'session_key' => $this->sessionKey]
        );
    }
}
