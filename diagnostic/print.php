<?php include('./constant/layout/head.php'); ?>
<?php include('./constant/connect.php'); ?>

<div class="">
    <div class="container-fluid" style="background-color: #ffffff;">
        
        <?php
        $order_id = $_GET['id'];
        $productSql = "SELECT * FROM orders WHERE order_id = '$order_id'";
        $productData = $connect->query($productSql);
        $row = $productData->fetch_array();

        $userSql = "SELECT * FROM users WHERE user_id= '".$row['user_id']."'";
        $userData = $connect->query($userSql);
        $row1 = $userData->fetch_array();

        $clientSql = "SELECT * FROM tbl_client WHERE id = '".$row['client_name']."'";
        $clientData = $connect->query($clientSql);
        $data1 = $clientData->fetch_assoc();
        ?>

        <div class="row">
            <div class="col-lg-12">
                <div class="card">
                    <div class="card-title">
                        <div class="float-left">
                            <h2 class="mb-0" style="color: black;">Invoice #<?php echo $row['order_id']; ?></h2>
                        </div> 
                        <div class="float-right"> 
                            Date: <?php echo $row['order_date']; ?>
                        </div>
                    </div>
                    <hr>

                    <div class="card-body">
                        <div class="row mb-4">
                            <div class="col-sm-4 mt-4">
                                <?php
                                $webSql = "SELECT * FROM manage_website";
                                $webData = $connect->query($webSql);
                                $web = $webData->fetch_array();
                                ?>
                                <br>
                                <img class="profile-img" src="./assets/uploadImage/Logo/<?=$web['invoice_logo']?>" style="height:100px;width:auto;">
                            </div>
                            <div class="col-sm-4">
                                <br>
                                <h5 class="mb-3" style="color: black;">From:</h5>                                            
                                <h3 class="text-dark mb-1"><?=$row1['username'];?></h3>
                                <div><?php echo $web['currency_code']; ?></div>
                                <div>Email: <?=$row1['email']?></div>
                                <div>Contact: <?php echo $web['short_title']; ?></div>
                            </div>
                            <div class="col-sm-4">
                                <br>
                                <h5 class="mb-3" style="color: black;">To:</h5>
                                <h3 class="text-dark mb-1"><?= $data1['name']; ?></h3>                                            
                                <div><?= $data1['address']; ?></div>
                                <div>Phone: <?= $data1['mob_no']; ?></div>
                            </div>
                        </div>

                        <div class="table-responsive-sm">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th class="center">#</th>
                                        <th>Test Name</th>
                                        <th class="right">Rate</th>
                                        <th class="center">Qty</th>
                                        <th class="right">Total</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php 
                                    $itemSql = "SELECT * FROM order_item WHERE order_id = '$order_id'";
                                    $itemData = $connect->query($itemSql);
                                    $no = 1;
                                    while($itemRow = $itemData->fetch_array()) {
                                        $productSql = "SELECT * FROM product WHERE product_id='".$itemRow['product_id']."'";
                                        $productData = $connect->query($productSql);
                                        $productRow = $productData->fetch_array();
                                    ?>
                                    <tr>
                                        <td class="center"><?= $no++; ?></td>
                                        <td class="left strong"><?= $productRow['product_name']; ?></td>
                                        <td class="right"><?= $itemRow['rate']; ?></td>
                                        <td class="center"><?= $itemRow['quantity']; ?></td>
                                        <td class="right"><?= $itemRow['total']; ?></td>
                                    </tr>
                                    <?php } ?>
                                </tbody>
                            </table>
                        </div>

                        <div class="row">
                            <div class="col-lg-8 col-sm-5 p-2 ">
                                <img src="https://www.ijournalhs.org/articles/2019/12/2/images/indianjhealthsci_2019_12_2_174_259633_t2.jpg" width="500px">
                                <img src="assets/myimages/stamp.png" width="200px" class="ml-3">
                            </div>
                            <div class="col-lg-4 col-sm-5 ml-auto">
                                <table class="table table-clear">
                                    <tbody>
                                        <tr>
                                            <td class="left"><strong class="text-dark">Subtotal</strong></td>
                                            <td class="right"><?= $row['sub_total']; ?></td>
                                        </tr>
                                        <tr>
                                            <td class="left"><strong class="text-dark">Discount (<?= $row['discount']; ?>%)</strong></td>
                                            <td class="right">
                                                <?php 
                                                $discount = $row['sub_total'] * ($row['discount'] / 100);
                                                echo number_format($discount, 2); 
                                                ?>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td class="left"><strong class="text-dark">GST (<?= $row['gst_rate']; ?>%)</strong></td>
                                            <td class="right">
                                                <?php 
                                                $gst_rate = ($row['sub_total'] - $discount) * ($row['gst_rate'] / 100);
                                                echo number_format($gst_rate, 2); 
                                                ?>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td class="left"><strong class="text-dark">Total</strong></td>
                                            <td class="right"><strong class="text-dark"><?= $row['grand_total']; ?></strong></td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <div class="card-footer bg-white">
                        <p class="mb-0">Thank you for your business!</p>
                    </div>
                </div>

                <!-- Buttons for Print and Go Back, hidden on print -->
                <input id="printbtn" type="button" class="btn btn-success btn-flat m-b-30 m-t-30" value="Print Invoice" onclick="window.print();">
                <input id="backbtn" type="button" class="btn btn-danger btn-flat m-b-30 m-t-30" value="Go Back" onclick="goBack();">
            </div>
        </div>
    </div>
</div>

<?php include('./constant/layout/footer.php'); ?>

<style>
/* Hide buttons when printing */
@media print {
    #printbtn, #backbtn {
        display: none;
    }
}
</style>

<script>
function goBack() {
    window.history.back();
}
</script>
