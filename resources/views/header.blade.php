<header id="home">   
    <div class="main-nav">
      <div class="container">
        <div class="navbar-header">
          <button type="button" class="navbar-toggle" data-toggle="collapse" data-target=".navbar-collapse">
            <span class="sr-only">Toggle navigation</span>
            <span class="icon-bar"></span>
            <span class="icon-bar"></span>
            <span class="icon-bar"></span>
          </button>
          <a class="navbar-brand" href="index.php">
            <!--<h1><img class="img-responsive" src="../images/tide.png" alt="logo"></h1>-->
          </a>                    
        </div>
        <div class="collapse navbar-collapse">
          <ul class="nav navbar-nav navbar-right">                 
            <li class="scroll <?php if (Request::segment(1) == '') echo 'active' ?>"><a href="/index.php">Home</a></li> 
            <li class="scroll <?php if (Request::segment(1) == 'about-us') echo 'active' ?>"><a href="/about-us">About Us</a></li>                    
         </ul>
        </div>
      </div>
    </div><!--/#main-nav-->
  </header><!--/#home-->