<?php

require APP_PATH . DIRECTORY_SEPARATOR . 'models' . DIRECTORY_SEPARATOR . 'Order.php';
require APP_PATH . DIRECTORY_SEPARATOR . 'models' . DIRECTORY_SEPARATOR . 'Dispute.php';

class DisputeController extends Controller
{
    public function create(): void
    {
        require_trade_access();
        $order = $this->findOrder(); $this->authorize($order);
        $this->view('disputes.form', ['title'=>'Open Dispute','order'=>$order,'errors'=>[],'old'=>[]]);
    }

    public function store(): void
    {
        require_trade_access();
        $order=$this->findOrder(); $this->authorize($order);
        $reason=trim($_POST['reason']??''); $details=trim($_POST['details']??''); $evidence=trim($_POST['evidence_notes']??''); $errors=[];
        if(!in_array($reason,['non_payment','non_delivery','wrong_card','condition_mismatch','counterfeit_concern','other'],true))$errors[]='Choose a valid dispute reason.';
        if(mb_strlen($details)<20||mb_strlen($details)>3000)$errors[]='Details must be between 20 and 3,000 characters.';
        if(mb_strlen($evidence)>3000)$errors[]='Evidence notes must be 3,000 characters or fewer.';
        if($errors!==[]){$this->view('disputes.form',['title'=>'Open Dispute','order'=>$order,'errors'=>$errors,'old'=>compact('reason','details','evidence')]);return;}
        try{(new Dispute())->open($order,(int)current_user()['id'],$reason,$details,$evidence);flash('success','Dispute opened. Settlement is frozen pending admin review.');}
        catch(RuntimeException $exception){flash('error',$exception->getMessage());}
        redirect('/orders/show?id='.(int)$order['id']);
    }

    private function findOrder(): array { $order=(new Order())->find((int)($_GET['order_id']??0)); if(!$order){http_response_code(404);echo'404 - Order not found';exit;} return $order; }
    private function authorize(array $order): void { $id=(int)current_user()['id']; if((int)$order['buyer_id']!==$id&&(int)$order['seller_id']!==$id){http_response_code(404);echo'404 - Order not found';exit;} }
}
