<?php
require 'vendor/autoload.php';
require __DIR__ . '/libs/BTX24.php';
require __DIR__ . '/libs/Num2str.php';

use PhpOffice\PhpWord\Element\TextRun;

$BX24 = new BTX24();
$num2str = new Num2str();


$invoice_list = $BX24->method('crm.invoice.list', array(
    "order"=> array(
        "ID" => "ASC"
    ),
    'filter' => array(
        'payed' => 'n',
    )
));

function writeToLog($data, $title = '')
{
    $log = "\n------------------------\n";
    $log .= date("Y.m.d G:i:s") . "\n";
    $log .= (strlen($title) > 0 ? $title : 'DEBUG') . "\n";
    $log .= print_r($data, 1);
    $log .= "\n------------------------\n";
    file_put_contents(getcwd() . '/log.txt', $log, FILE_APPEND);
    return true;
}

for ($i=0; $i<$invoice_list['total']; ++$i){

    $invoice = $BX24->method('crm.invoice.get', array(
        'id' => $invoice_list['result'][$i]['ID']
    ));

    $end_time =  strtok($invoice['result']['DATE_PAY_BEFORE'],'T');
    //текущая дата в Unix формате
    $currentDate = time();
    $currentFormatedDate = date('Y-m-d', $currentDate);

    $dateFromCrm = explode('-', $end_time);
    $dateCurrent = explode('-', $currentFormatedDate);

    if ($dateFromCrm[1] === $dateCurrent[1]){
        if ($dateCurrent[2] + 2 == $dateFromCrm[2]){
            $message = 'sendWithDoc';
        }
    }
    else if ($dateFromCrm[1] > $dateCurrent[1] && $dateFromCrm[1] == $dateCurrent[1]+1){
        $lastDay = date('t', $currentDate);
        //теперь в переменной $lastDay хранится последняя дата месяца
        if ($dateFromCrm[2]+$lastDay === $dateCurrent[2]+2){
            $message = 'sendWithDoc';
        }
    }

    if ($message === 'sendWithDoc') {

        $responEmail = $invoice['result']['RESPONSIBLE_EMAIL'];
        $responName = $invoice['result']['RESPONSIBLE_NAME'];

        $invoice_num = $invoice['result']['PRODUCT_ROWS']['0']['ID'];
        $create_time = strtok($invoice['result']['DATE_INSERT'], 'T');

        $contact_fio = $invoice['result']['INVOICE_PROPERTIES']['FIO'];
        $contact_phone = $invoice['result']['INVOICE_PROPERTIES']['PHONE'];
        $contact_email = $invoice['result']['INVOICE_PROPERTIES']['EMAIL'];
        $deal_id = $invoice['result']['UF_DEAL_ID'];

        $product_name = $invoice['result']['PRODUCT_ROWS']['0']['PRODUCT_NAME'];
        $words = explode('.', $invoice['result']['PRODUCT_ROWS']['0']['QUANTITY']);
        $col = $words['0'];

        $words = explode('.', $invoice['result']['PRICE']);
        if (array_key_exists('1', $words)) {
            if ($words['1'] > 0) {
                $price = $invoice['result']['PRICE'];
            } else {
                $price = $words['0'] . '.00';
            }
        } else {
            $price = $invoice['result']['PRICE'] . '.00';
        }

        $words = explode('.', $invoice['result']['PRODUCT_ROWS']['0']['PRICE']);
        if ($words['1'] > 0) {
            $total_price = $invoice['result']['PRODUCT_ROWS']['0']['PRICE'];
            $on_words = $num2str->numb2string($total_price);
        } else {
            $total_price = $words['0'] . '.00';
            $on_words = $num2str->numb2string($total_price);
        }


        //              Создание документа по шаблону

        //Берём шаблон файла
        $templatePath = __DIR__ . '/invoice_template.docx';
        $templateProcessor = new \PhpOffice\PhpWord\TemplateProcessor($templatePath);

        $text = new TextRun();

        //          Пишем все данные в документ и сохраняем его

        $invoice_num = $text->addText($invoice_num, array('size'=>'21', 'name'=>'Calibri', 'bold'=> true));
        $templateProcessor->setComplexValue('invoice.num', $invoice_num);

        $create_time = $text->addText($create_time, array('size'=>'21', 'name'=>'Calibri', 'bold'=> true));
        $templateProcessor->setComplexValue('create.time', $create_time);

        $end_time = $text->addText($end_time, array('size'=>'10.5', 'name'=>'Calibri', 'bold'=> false));
        $templateProcessor->setComplexValue('end.time', $end_time);

        $contact_fio = $text->addText($contact_fio, array('size'=>'10.5', 'name'=>'Calibri', 'bolt'=> false));
        $templateProcessor->setComplexValue('contact.fio', $contact_fio);

        $contact_phone = $text->addText($contact_phone, array('size'=>'10.5', 'name'=>'Calibri', 'bolt'=> false));
        $templateProcessor->setComplexValue('contact.phone', $contact_phone);

        $product_name = $text->addText($product_name, array('size'=>'10.5', 'name'=>'Calibri', 'bolt'=> false));
        $templateProcessor->setComplexValue('product.name', $product_name);

        $col = $text->addText($col, array('size'=>'10.5', 'name'=>'Calibri', 'bolt'=> false));
        $templateProcessor->setComplexValue('col', $col);
        $templateProcessor->setComplexValue('col', $col);

        $price = $text->addText($price, array('size'=>'10.5', 'name'=>'Calibri', 'bold'=> false));
        $templateProcessor->setComplexValue('price', $price);

        $total_price = $text->addText($total_price, array('size'=>'10.5', 'name'=>'Calibri', 'bolt'=> false));
        $templateProcessor->setComplexValue('total.price', $total_price);
        $templateProcessor->setComplexValue('total.price', $total_price);
        $templateProcessor->setComplexValue('total.price', $total_price);

        $on_words = $text->addText($on_words, array('size'=>'10.5', 'name'=>'Calibri', 'bold'=> true));
        $templateProcessor->setComplexValue('on.words', $on_words);

        $docResultPath = __DIR__ . '/results/Документ_ID_'.$invoice['result']['PRODUCT_ROWS']['0']['ID'].'.docx';
        $templateProcessor->saveAs($docResultPath);

        //Формируем письмо
        $file = file_get_contents($docResultPath);

        //ЗДЕСЬ ТЕМА ПИСЬМА
        $subject = $invoice['result']['INVOICE_PROPERTIES']['FIO'] .', отправляю Вам счёт для оплаты';
        //ЗДЕСЬ ТЕКСТ ПИСЬМА
        $description = $invoice['result']['INVOICE_PROPERTIES']['FIO'] .', добрый день!

        Вам пишет , списать-долги.рф. Как мы и договорились, отправляю Вам счёт для оплаты. Как только Вы оплатите счёт, я передам наш договор с Отдел Исполнения.


        Обратите внимание, с моменты оплата счёта и начала работы:

        - Вы освобождаетесь от необходимости оплачивать кредиты и долги

        - Ни в коем случае не вносите платежи по кредитам!



        Если у Вас возникнут какие-либо вопросы, я буду рад на них ответить! Смело свяжитесь со мной по телефону (ВАШ НОМЕР ТЕЛЕФОНА) или через WhatsUp (ВАШ НОМЕР WHATSUP)

        Как и договорились, будем с Вами на связи в ближайшее время!

        С уважением,

        {ИМЯ СОТРУДНИКА}

        Финансовый эксперт компании (ВАША КОМПАНИЯ)

        www.списать-долги.рф';


    //              Отправляем письмо контакту
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
                        'MESSAGE_TO' => implode(
                            ' ',
                            array($invoice['result']['INVOICE_PROPERTIES']['FIO'], '<' . $responEmail . '>')
                        ),

                    ),
                    'FILES' => array(
                        'fileData' => array(
                            'Документ.docx',
                            base64_encode($file)
                        )
                    )
                )
            )
        );
        writeToLog($mail, 'Письмо');
        //       Обновляем статуса счета
        $BX24->method('crm.invoice.update',array(
            'id' => $invoice['result']['PRODUCT_ROWS']['0']['ID'],
            "fields" => array(
                'STATUS_ID' => 'S'
            )
        ));

        //usleep(500000);
        //Удаляем документ из сервера
        unlink($docResultPath);
        $message = '';
    }
}