<?php
class com_meego_packages_workflow_action_distillreview implements ezcWorkflowServiceObject
{
    /**
     * @todo: docs
     */
    public function execute(ezcWorkflowExecution $execution)
    {
        $form = midgardmvc_ui_forms_generator::get_by_guid($execution->getVariable('review_form'));
        $instance = new midgardmvc_ui_forms_form_instance($execution->getVariable('review'));
        $review = midgardmvc_ui_forms_store::load_form($form, $instance);

        $items = $form->items;
        $boolean_count = 0;
        $positive = 0;

        $package = new com_meego_package($instance->relatedobject);

        foreach ($items as $key => $item)
        {
            if (   $key == "redirect_link"
                || $key == "execution")
            {
                continue;
            }

            try
            {
                $field = new midgardmvc_ui_forms_form_field($key);
            }
            catch(Exception $e)
            {
                midgardmvc_core::get_instance()->log('Invalid field detected in distill review: ' . $key . ', value: ' . $item, 'warning');
            }

            // todo: ugly hardcoded check, but what can we do..
            if ($field->title == "Should the application be in this application catalog?")
            {
                // if the answer belongs to a certain field then we process
                // this field must be a boolean too
                if ($item instanceof midgardmvc_helper_forms_field_boolean)
                {
                    if ($item->get_value())
                    {
                        // add +1 to the package score
                        $package->metadata->score++;
                    }
                    else
                    {
                        //  oh yes, we do give -1 points too ;)
                        $package->metadata->score--;
                    }
                    $res = $package->update();

                    if (! $res)
                    {
                        //update failed; do what?
                    }
                }
            }
            else
            {
                // otherwise
                continue;
            }
        }

        // pass on the score
        $execution->setVariable('distilled_review', $package->metadata->score);
    }

    public function __toString()
    {
        return 'distillreview';
    }
}
