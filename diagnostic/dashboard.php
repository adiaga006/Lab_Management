<?php error_reporting(1); ?>
<?php include('./constant/layout/head.php');?>
<?php include('./constant/layout/header.php');?>

<?php include('./constant/layout/sidebar.php');?>   
<?php 

$sql = "SELECT * FROM product WHERE status = 1";
$query = $connect->query($sql);
$countProduct = $query->num_rows;

$orderSql = "SELECT * FROM orders WHERE order_status = 1";
$orderQuery = $connect->query($orderSql);
$countOrder = $orderQuery->num_rows;

$totalRevenue = 0;

while ($orderResult = $orderQuery->fetch_assoc()) {
    //echo $orderResult['paid'];exit;
    $totalRevenue += $orderResult['paid'];

}

$lowStockSql = "SELECT * FROM product WHERE quantity <= 3 AND status = 1";
$lowStockQuery = $connect->query($lowStockSql);
$countLowStock = $lowStockQuery->num_rows;

$userwisesql = "SELECT users.username , SUM(orders.grand_total) as totalorder FROM orders INNER JOIN users ON orders.user_id = users.user_id WHERE orders.order_status = 1 GROUP BY orders.user_id";
$userwiseQuery = $connect->query($userwisesql);
$userwieseOrder = $userwiseQuery->num_rows;

$connect->close();

?>
  
<style type="text/css">
    .ui-datepicker-calendar {
        display: none;
    }
</style>
        
        <div class="page-wrapper">
            
            <div class="row page-titles">
                <div class="col-md-12 align-self-center">
                   </div>
                
            </div>
            
            
            <div class="container-fluid">
                
                
        

                      <div class="row">
                        
                    <div class="col-md-3 dashboard">
                        <div class="card" style="background: #2BC155">
                            <div class="media widget-ten">
                                <div class="media-left meida media-middle">
                                    <span><i class="ti-book f-s-40"></i></span>
                                </div>
                                <div class="media-body media-text-right">
                                 
                            
                                    <h2 class="color-white"><?php echo $countProduct; ?></h2>
                                    <a href="test.php"><p class="m-b-0">Total Product</p></a>
                                </div>
                            </div>
                        </div>
                    </div>
                   
                   <?php if(isset($_SESSION['userId']) && $_SESSION['userId']==1) { ?>
                     <div class="col-md-3 dashboard">
                        <div class="card" style="background-color: #F94687">
                            <div class="media widget-ten">
                                <div class="media-left meida media-middle">
                                    <span><i class="ti-vector f-s-40"></i></span>
                                </div>
                                <div class="media-body media-text-right">
                                    
                            <h2 class="color-white"><?php echo $countOrder; ?></h2>
                                    <a href="invoice.php"><p class="m-b-0">Total Order</p></a>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php }?>
                
        <div class="col-md-3 dashboard">
        <div class="card" style="background-color:#ffc107;">
          <div class="media widget-ten">
            <div class="media-left meida media-middle">
                <span><i class="ti-calendar f-s-40"></i></span>
            </div>
            <div class="media-body media-text-right">
             <h1 style="color:white;"><?php echo date('d'); ?></h1>
            <p style="color:white;"><?php echo date('l') .' '.date('d').', '.date('Y'); ?></p>
          </div>
        </div> 
     </div>
       </div>
 <div class="col-md-3 dashboard">
        <div class="card" style="background-color:#009688;">
          <div class="media widget-ten">
            <div class="media-left meida media-middle">
                 <span><i class="ti-vector f-s-40"></i></span>
           
          </div>

           <div class="media-body media-text-right">
             <h1 style="color:white;"><?php if($totalRevenue) {
                echo $totalRevenue;
                } else {
                    echo '0';
                    } ?></h1>
            <p style="color:white;">Total Revenue</p>
          </div>
        </div> 
    </div>
    </div>
    <?php if(isset($_SESSION['userId']) && $_SESSION['userId']==1) { ?>
     <div class="col-md-12">
<div class="card">
                            <div class="card-header">
                                <strong class="card-title">User Wise Order</strong>
                            </div>
                            <div class="card-body">
                                <table class="table">
                                    <thead>
                                        <tr>
                                          
                                           <th scope="col">Name</th>
                                           <th scope="col">Orders Amount</th>
                                      </tr>
                                  </thead>
                                  <tbody>
                                     <?php while ($orderResult = $userwiseQuery->fetch_assoc()) { ?>
                                     <tr>
                            <td><?php echo $orderResult['username']?></td>
                            <td><?php echo $orderResult['totalorder']?></td>
                            
                        </tr>
                              <?php } ?>      
                                   
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            <?php }?>

                
            </div>
        </div>
       <mark><center><strong>For any paid support, Contact <span style="color: blue">Mayuri K.</span>, on email mayuri.infospace@gmail.com </strong></center>
</mark> 
    </div>
            
            <?php include ('./constant/layout/footer.php');?>
        <script>
        $(function(){
            $(".preloader").fadeOut();
        })
        </script>