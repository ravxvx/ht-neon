/* Runs in <head>: paints any full-screen preloader dark before the first frame. */
(function(){
	var css='[class*="preloader"],[id*="preloader"],[class*="page-loader"],[id*="page-loader"]{background:#110a1b !important}';
	var st=document.createElement('style');st.id='htn-preloader-css';st.textContent=css;
	(document.head||document.documentElement).appendChild(st);
})();
