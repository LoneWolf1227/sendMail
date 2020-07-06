<?php

$BX24 = new BTX24();
$num2str = new Num2str();


$invoice_list = $BX24->method('crm.invoice.list', array(
    "order" => array(
        "ID" => "ASC"
    ),
    'filter' => array(
        'payed' => 'n',
        'status_id' => 'S'
    )
));


for ($i=0; $i<$invoice_list['total']; ++$i) {

    $invoice = $BX24->method('crm.invoice.get', array(
        'id' => $invoice_list['result'][$i]['ID']
    ));

        $end_time = strtok($invoice['result']['DATE_PAY_BEFORE'], 'T');

        $responEmail = $invoice['result']['RESPONSIBLE_EMAIL'];
        $responName = $invoice['result']['RESPONSIBLE_NAME'];

        $contact_email = $invoice['result']['INVOICE_PROPERTIES']['EMAIL'];
        $deal_id = $invoice['result']['UF_DEAL_ID'];

        $subject = $invoice['result']['INVOICE_PROPERTIES']['FIO'] .', отправляю Вам счёт для оплаты';
        //ЗДЕСЬ ТЕКСТ ПИСЬМА
        $description = $invoice['result']['INVOICE_PROPERTIES']['FIO'] .', добрый день!

        Вам пишет , списать-долги.рф. Пожалуйста пополните счет который мы вам отправили. Последний срок '
        . $end_time;

        $mail = $BX24->method('crm.activity.add',
            array(
                'fields' => array(
                    "SUBJECT" => $subject,
                    "DESCRIPTION" => $description,
                    "DESCRIPTION_TYPE" => 2,//text,html,bbCode type id in: CRest::call('crm.enum.contenttype');
                    "COMPLETED" => "Y",//send now
                    "DIRECTION" => 2,// CRest::call('crm.enum.activitydirection');
                    "OWNER_ID" => $deal_id,
                    "OWNER_TYPE_ID" => 2, // CRest::call('crm.enum.ownertype');
                    "TYPE_ID" => 4, // CRest::call('crm.enum.activitytype');
                    "COMMUNICATIONS" => array(
                        array(
                            'VALUE' => $contact_email,
                            'ENTITY_ID' => $deal_id,
                            'ENTITY_TYPE_ID' => 2// CRest::call('crm.enum.ownertype');
                        )
                    ),
                    'SETTINGS' => array(
                        'MESSAGE_FROM' => implode(
                            ' ',
                            array($responName, '<' . $responEmail . '>')
                        ),
                    ),
                )
            )
        );

    $message = '';
}