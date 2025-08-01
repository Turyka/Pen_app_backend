<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta http-equiv="X-UA-Compatible" content="ie=edge">
  <title>429 Error</title>

  <style>

@import url("https://fonts.googleapis.com/css?family=Montserrat:400,400i,700");
 *, *:after, *:before {
	 box-sizing: border-box;
}
 body {
	 background-color: #313942;
	 font-family: "Montserrat", sans-serif;
}
 main {
	 align-items: center;
	 display: flex;
	 flex-direction: column;
	 height: 100vh;
	 justify-content: center;
	 text-align: center;
}
 h1 {
	 color: #e7ebf2;
	 font-size: 12.5rem;
	 letter-spacing: 0.1em;
	 margin: 0.025em 0;
	 text-shadow: 0.05em 0.05em 0 rgba(0, 0, 0, 0.25);
	 white-space: nowrap;
}
 @media (max-width: 30rem) {
	 h1 {
		 font-size: 8.5rem;
	}
}
 h1 > span {
	 animation: spooky 2s alternate infinite linear;
	 color: #528cce;
	 display: inline-block;
}
 h2 {
	 color: #e7ebf2;
	 margin-bottom: 0.4em;
}
 p {
	 color: #ccc;
	 margin-top: 0;
}
 @keyframes spooky {
	 from {
		 transform: translatey(0.15em) scaley(0.95);
	}
	 to {
		 transform: translatey(-0.15em);
	}
}
 
  </style>
</head>
<body>
  <main>
    <h1>429</h1>
    <h2>Error: 429</h2>
    <p>Túl sokat probálkoztál, kérlek várj 1 percet</p>
  </main>
</body>
</html>