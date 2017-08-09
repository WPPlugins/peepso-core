<?php

class PeepSoAdminConfigLicense extends PeepSoAjaxCallback
{
    /*
     * Builds the required flot data set based on the request
     * @param PeepSoAjaxResponse $resp The response object
     */
    public function check_license(PeepSoAjaxResponse $resp)
    {
        if (!PeepSo::is_admin()) {
            $resp->success(FALSE);
            $resp->error(__('Insufficient permissions.', 'peepso-core'));
            return;
        }
		
        $plugins = $this->_input->val('plugins');
        $response = array();

        if(count($plugins)) {

            foreach ($plugins as $slug => $name) {

                PeepSoLicense::activate_license($slug, $name);

                $response[$slug] = (int)PeepSoLicense::check_license($name, $slug, TRUE);
            }
        }

        $resp->set('valid', $response);
        $resp->success(TRUE);
    }
}

// EOF