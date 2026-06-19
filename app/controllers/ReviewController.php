<?php

require APP_PATH . DIRECTORY_SEPARATOR . 'models' . DIRECTORY_SEPARATOR . 'MarketplaceReview.php';
require APP_PATH . DIRECTORY_SEPARATOR . 'models' . DIRECTORY_SEPARATOR . 'Order.php';
require_once APP_PATH . DIRECTORY_SEPARATOR . 'services' . DIRECTORY_SEPARATOR . 'NotificationService.php';

class ReviewController extends Controller
{
    public function store(): void
    {
        require_trade_access();
        $orderId=(int)($_GET['order_id']??0); $order=(new Order())->find($orderId); $buyerId=(int)current_user()['id'];
        if(!$order||(int)$order['buyer_id']!==$buyerId){http_response_code(404);echo'404 - Order not found';return;}
        $rating=(int)($_POST['rating']??0); $body=trim($_POST['body']??'');
        if($rating<1||$rating>5||mb_strlen($body)<10||mb_strlen($body)>2000){flash('error','Choose a 1-5 rating and write 10-2,000 characters.');redirect('/orders/show?id='.$orderId);}
        try{(new MarketplaceReview())->create($orderId,$buyerId,$rating,$body);NotificationService::send((int)$order['seller_id'],'order_review','New verified review','A buyer reviewed completed order #'.$orderId.'.','/sellers/show?id='.(int)$order['seller_id']);flash('success','Verified-purchase review published.');}
        catch(RuntimeException $exception){flash('error',$exception->getMessage());}
        redirect('/orders/show?id='.$orderId);
    }
}
