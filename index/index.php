<?php

function updateRecordInZoho ($url , $para)
{
        $ch = curl_init(); curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $para); 
        curl_setopt($ch, CURLOPT_URL, $url); 
        curl_setopt($ch, CURLOPT_POST, TRUE); curl_setopt($ch, CURLOPT_HEADER, TRUE); 
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); 
        $response = curl_exec($ch); 
        $response_info = curl_getinfo($ch); 
        curl_close($ch); 
        $response_body = substr($response, $response_info['header_size']);
        $app = json_decode($response_body,true) ;
    }


if (isset($_REQUEST)){
	if (!isset($_REQUEST['where'])) $_REQUEST['where'] = "";
}
	
if ( isset($_REQUEST['wipe'])) {
  session_destroy();
  header("Location: {$here}");

// already got some credentials stored?
} elseif(isset($_REQUEST['refresh'])) {
    $response = $XeroOAuth->refreshToken($oauthSession['oauth_token'], $oauthSession['oauth_session_handle']);
    if ($XeroOAuth->response['code'] == 200) {
        $session = persistSession($response);
        $oauthSession = retrieveSession();
    } else {
        outputError($XeroOAuth);
        if ($XeroOAuth->response['helper'] == "TokenExpired") $XeroOAuth->refreshToken($oauthSession['oauth_token'], $oauthSession['session_handle']);
    }

} elseif ( isset($oauthSession['oauth_token']) && isset($_REQUEST) ) {

    $XeroOAuth->config['access_token']  = $oauthSession['oauth_token'];
    $XeroOAuth->config['access_token_secret'] = $oauthSession['oauth_token_secret'];
    $XeroOAuth->config['session_handle'] = $oauthSession['oauth_session_handle'];

//    1 * 24 * 60 *
    $response = $XeroOAuth->request('GET', $XeroOAuth->url('Invoices', 'core'), array('If-Modified-Since' => gmdate("M d Y H:i:s",(time() - (1 * 24 * 60 * 60)))));
    if ($XeroOAuth->response['code'] == 200) {

        $accounts = $XeroOAuth->parseResponse($XeroOAuth->response['response'], $XeroOAuth->response['format']);
        if (!empty($accounts->Invoices[0]))
        {
            foreach ($accounts->Invoices[0]->Invoice as $row)
            {
                if ($row->Type == "ACCREC")
                {
                    $getRequest = "https://crm.zoho.com/crm/private/json/Invoices/searchRecords?" ;
                    $getRequest_param = "authtoken=" . $authToken . "&scope=crmapi&criteria=(Xero Invoice Id:".$row->InvoiceID[0].")" ;
                    $getInvoice = getInvoice($getRequest,$getRequest_param) ;
                    if (empty($getInvoice['response']['nodata']['message']))
                    {
                        $invoices = $getInvoice['response']['result']['Invoices']['row']['FL'] ;
                        foreach ($invoices as $invoice)
                        {
                            if ($invoice['val'] == "Status")
                            {
                                if ($invoice['content'] != "PAID")
                                {
                                    foreach ($invoices as $invoice)
                                    {
                                        if ($invoice['val'] == "INVOICEID")
                                        {
                                            $xml = "" ;
                                            $xml .= '<Invoices>
                                                <row no="1">
                                                <FL val="Status">Paid</FL>
                                                </row>
                                                </Invoices>' ;
                                            $getRequest = "https://crm.zoho.com/crm/private/xml/Invoices/updateRecords?" ;
                                            $getRequest_param = "authtoken=" . $authToken . "&scope=crmapi&id=".$invoice['content']."&xmlData=".$xml ;
                                            $result = getInvoice($getRequest,$getRequest_param) ;
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
                if ($row->Type == "ACCPAY")
                {
                    $getRequest = "https://crm.zoho.com/crm/private/json/PurchaseOrders/searchRecords?" ;
                    $getRequest_param = "authtoken=" . $authToken . "&scope=crmapi&criteria=(Xero Bill Id:".$row->InvoiceID[0].")" ;
                    $getInvoice = getInvoice($getRequest,$getRequest_param) ;
                    if (empty($getInvoice['response']['nodata']['message']))
                    {
                        $invoices = $getInvoice['response']['result']['PurchaseOrders']['row']['FL'] ;
                        foreach ($invoices as $invoice)
                        {
                            if ($invoice['val'] == "Status")
                            {
                                if ($invoice['content'] != "PAID")
                                {
                                    foreach ($invoices as $invoice)
                                    {
                                        if ($invoice['val'] == "PURCHASEORDERID")
                                        {
                                            $xml = "" ;
                                            $xml .= '<PurchaseOrders>
                                                <row no="1">
                                                <FL val="Status">Paid</FL>
                                                </row>
                                                </PurchaseOrders>' ;
                                            $getRequest = "https://crm.zoho.com/crm/private/xml/PurchaseOrders/updateRecords?" ;
                                            $getRequest_param = "authtoken=" . $authToken . "&scope=crmapi&id=".$invoice['content']."&xmlData=".$xml ;
                                            $result = getInvoice($getRequest,$getRequest_param) ;
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }
    } else {
        outputError($XeroOAuth);
    }



}
