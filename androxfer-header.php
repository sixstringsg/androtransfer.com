<body>
	<!--[if lt IE 7]>
		<p class="chromeframe">You are using an <strong>outdated</strong> browser. Please <a href="http://browsehappy.com/">upgrade your browser</a> or <a href="http://www.google.com/chromeframe/?redirect=true">activate Google Chrome Frame</a> to improve your experience.</p>
	<![endif]-->

	<div class="lens-flare">
		<div class="header-container">
			<header class="wrapper clearfix">
				<a href="http://androtransfer.com">
<pre style="white-space: pre;">
                 _         __  __ __           
  __ _ _ __   __| |_ __ ___\ \/ // _| ___ _ __ 
 / _` | '_ \ / _` | '__/ _ \\  /| |_ / _ \ '__|
| (_| | | | | (_| | | | (_) /  \|  _|  __/ |   
 \__,_|_| |_|\__,_|_|  \___/_/\_\_|  \___|_|   
                          /// powered by       
                         /// Bytemark.co.uk    
                                               
</pre>
				</a>
				<div class="partners">
					<h3>Partners / Friends:</h3>
					<a rel="external" href="http://www.bytemark.co.uk/r/androtransfer">:BYTEMARK HOSTING</a>
					<a rel="external" href="http://hxcmusic.com/">HXC MUSIC</a>
					<a rel="external" href="https://plus.google.com/u/0/112132608212015447534/posts">+SCAR45</a>
				</div>
			</header>
		</div>
		<div id="devs" class="andro-column">
			<h2>developers</h2>
			<ul>
				<?php foreach($users as $user): ?>
				<li class='<?= $currentDeveloper == $user ? "active" : "" ?>'>//<a href='?developer=<?= $user ?>'><?= $user ?></a></li>
				<?php endforeach ?>	
			</ul>
		</div><!-- end devs -->
