<?php
namespace Modules\Wazone;
require_once 'example.php';
$api = new example();
?>
<!DOCTYPE html>
<html>
  <head>
    <meta charset="utf-8"/>
    <title>Script - Activator</title>
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bulma/0.8.2/css/bulma.min.css" crossorigin="anonymous"/>
    <style type="text/css">
      body, html {
        background: #F4F5F7;
      }
    </style>
  </head>
  <body>
    <div class="container" style="padding-top: 20px;"> 
      <div class="section">
        <div class="columns is-centered">
          <div class="column is-two-fifths">
            <center>
              <h1 class="title" style="padding-top: 20px">Wazone Module Activator</h1><br>
            </center>
            <div class="box">
             <?php
              $license_code = null;
              $client_name = null;
              if(!empty($_POST['license'])&&!empty($_POST['client'])){
                $license_code = strip_tags(trim($_POST["license"]));
                $client_name = strip_tags(trim($_POST["client"])); 
                $activate_response = $api->activate_license($license_code, $client_name);
                if(empty($activate_response)){
                  $msg = 'Server is unavailable.';
                }else{
                  $msg = $activate_response['message'];
                }
                if($activate_response['status'] != true){ ?>
                  <form action="/Modules/Wazone/index.php" method="POST">
                    <div class="notification is-danger is-light"><a href="/Modules/Wazone/deac.php"><?php echo ucfirst($msg)." CLICK HERE TO DE-ACTIVATE!"; ?></a></div>
                    <div class="field">
                      <label class="label">License code</label>
                      <div class="control">
                        <input class="input" type="text" placeholder="Enter your purchase/license code" name="license" required>
                      </div>
                    </div>
                    <div class="field">
                      <label class="label">Your name</label>
                      <div class="control">
                        <input class="input" type="text" placeholder="Enter your name/envato username" name="client" required>
                      </div>
                    </div>
                    <div style='text-align: right;'>
                      <button type="submit" class="button is-link is-rounded">Activate</button>
                    </div>
                  </form><?php
                }else{ ?>
                  <div class="notification is-success is-light"><a href="/public/admin/modules"><?php echo ucfirst($msg)." CLICK HERE TO CONTINUE!"; ?></a></div><?php 
                }
              }else{ ?>
                <form action="/Modules/Wazone/index.php" method="POST">
                  <div class="field">
                    <label class="label">License code</label>
                    <div class="control">
                      <input class="input" type="text" placeholder="Enter your purchase/license code" name="license" required>
                    </div>
                  </div>
                  <div class="field">
                    <label class="label">Your name</label>
                    <div class="control">
                      <input class="input" type="text" placeholder="Enter your name/envato username" name="client" required>
                    </div>
                  </div>
                  <div style='text-align: right;'>
                    <button type="submit" class="button is-link is-rounded">Activate</button>
                  </div>
                </form><?php 
              } ?>
            </div>
          </div>
        </div>
      </div>
    </div>
    <div class="content has-text-centered">
      <p>Copyright <?php echo date('Y'); ?> Arrocy, All rights reserved.</p><br>
    </div>
  </body>
</html>