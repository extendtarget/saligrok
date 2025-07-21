<?php
namespace Modules\Wazone\Resources\views;

use Modules\Wazone\Http\Controllers\WazoneController;
$conn = new WazoneController;
?>

@extends('admin.layouts.master')
@section("title") Activation - Wazone
@endsection
@section('content')
<!DOCTYPE html>
<html>
  <head>
    <meta charset="utf-8"/>
    <title>Activator</title>
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
          <div class="column">
            <center>
              <h1 class="title" style="padding-top: 20px">Module Activator</h1><br>
            </center>
            <div class="box">
             <?php
              if (!empty($conn->validate())) {redirect("/public/wazone/settings"); exit;}
              //$license = null;
              if(!empty($_GET['license'])){
                $license = strip_tags(trim($_GET["license"]));
                $response = $conn->activate($license);
                if(empty($response['success'])){ 
                  $msg = 'Activation Failed! Invalid License Key!<br><br>Please login to <a href="https://visimisi.net/my-account" target="_blank" rel="noopener noreferrer">MY ACCOUNT</a><br><br>Click on LICENSE KEYS.<br>If activation limit reached, click Deactivate button,<br>then Activate on this server!'; ?>
                  <form action="<?php $_SERVER['PHP_SELF'] ?>" method="GET">
                    <div class="notification is-danger is-light"><?php echo ucfirst($msg); ?></div>
                    <div class="field">
                      <label class="label">License Key</label>
                      <div class="control">
                        <input class="input" type="text" placeholder="Enter your License Key" name="license" required>
                      </div>
                    </div>
                    <div style='text-align: right;'>
                      <button type="submit" class="button is-link is-rounded">Activate</button>
                    </div>
                  </form><?php
                }else{
                  $msg = 'Activation Success!<br><br>Please wait, getting things ready...<br><br><a href="/public/wazone/settings">click HERE</a> if it is not redirecting after 5 seconds'; ?>
                  <div class="notification is-success is-light"><?php echo ucfirst($msg); ?></div>
                  <script>setTimeout(function () {window.location.href = "/public/wazone/settings";}, 4000);</script>
          <?php }
              }else{ ?>
                <form action="<?php $_SERVER['PHP_SELF'] ?>" method="GET">
                  <div class="field">
                    <label class="label">License Key</label>
                    <div class="control">
                      <input class="input" type="text" placeholder="Enter your License Key" name="license" required>
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
@endsection