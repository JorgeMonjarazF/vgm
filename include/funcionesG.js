//////////////////////////////////////////////////////////////////
// Funciones de JavaScript
// Autor: Nestor Perez
//////////////////////////////////////////////////////////////////

function test(){
	alert("Aqui funciona...");
}

// ------------- AJAX ----------------------------------------------
function objetoAjax(){
	var xmlhttp=false;
	try {
		xmlhttp = new ActiveXObject("Msxml2.XMLHTTP");
	} catch (e) {
		try {
			xmlhttp = new ActiveXObject("Microsoft.XMLHTTP");
		} catch (E) {
			xmlhttp = false;
		}
	}

	if (!xmlhttp && typeof XMLHttpRequest!='undefined') {
		xmlhttp = new XMLHttpRequest();
	}
	return xmlhttp;
}
// -----------------
// PAGINACION AJAX
// -----------------
var cadenaX='';
var contadorSearch=0;
function paginaAjax(nropagina){
	var aleatorio=Math.random();
	ajax=objetoAjax();
	pageName2=document.frm1.pageName.value;
	for(var i=1;i<12;++i){
		tx=eval("document.frm1.keyword"+i);
		if(tx!= undefined){
			tx=eval("document.frm1.keyword"+i+".value");
			if(tx!=''){
				tNum=eval('t'+i+'="'+tx+'"');
				//Se arma la cadena
				cadenaX=cadenaX+'&keyword'+i+'='+tNum;
				contadorSearch++;
			}
		}
	}
	if(nropagina==undefined & contadorSearch==0){
		cadenaX='';
	}
	//uso del medoto GET
	ajax.open("GET", pageName2+"?page="+nropagina+cadenaX,true);
	ajax.onreadystatechange=function() {
		if (ajax.readyState==4) {
			//mostrar resultados en esta capa
			document.getElementById('result').innerHTML = ajax.responseText;
		}
	}
	contadorSearch=0;
	ajax.send(null)
}
// ffffffffffffffffff AJAX fffffffffffffffffff
function getFormValues(modo,action){
	var cadenaX='';
	var contX=0;
	var n;
	var v;
	
	divResultado = document.getElementById('result');
	var n=document.frm1.elements.length;
	for(var i=0;i<n;++i){
		var tx=document.frm1.elements[i].name;
		var txType=document.frm1.elements[i].type;
		
		tx2="";
		if(tx!=''){
			if(txType=="checkbox"){
				if( document.frm1.elements[i].checked ){
					tx2='1';
				}
				else{
					tx2='0';
				}
			}			
			else if(txType=="radio"){
				if( document.frm1.elements[i].checked ){					
					if (document.frm1.elements[i].value=='1' ){						
						tx2='1';
					}
					else{
						tx2='0';						
					}
				}
			}			
			else{
				tx2=document.frm1.elements[i].value;
				tx2 = escape(tx2);				
			}
					
			if(tx2!=''){				
				tNum=eval('t'+i+'="'+tx2+'"');				
				if(contX==0){
					d='';
				}
				else{
					d='&';
				}
				//Se arma la cadena
				cadenaX=cadenaX+d+tx+'='+tNum;
				contX++;
			}
		}
	}
	cadenaX = cadenaX+'&modo='+modo;
	//alert(cadenaX);
	ajax=objetoAjax();
	ajax.open("POST", action,true);
	ajax.onreadystatechange=function() {
		if(ajax.readyState==4){
			divResultado.innerHTML = ajax.responseText;
		}
	}
	ajax.setRequestHeader("Content-Type","application/x-www-form-urlencoded");
	ajax.send(cadenaX);
	cadenaX='';
}

// -----------------------------------------------
// Funciones para el módulo de contenedores.
// -----------------------------------------------
function conteUpdate (f,doc,id){
	f.action=doc+"?modo=actualizar&idConte="+id;
	f.submit();
}
function conteRec (f,doc){
	f.action=doc+"?modo=guardar";
	f.submit();
}

// ffffffffffffffffffffffffffffffffffffffffffffffffffffffff
function openerSubmit(){
	opener.document.frm1.submit();
	window.close();
}
// ffffffffffffffffffffffffffffffffffffffffffffffffffffffff
function newWindow(pag,ancho,alto) {
	propi='width='+ancho+',height='+alto+',left=5,top=30,menubar=yes,scrollbars=yes,location=yes,directories=no,toolbar=no,resizable=yes,menubar=yes,scrollbars=yes,status=yes';
	//'width=493,height=393,location=0,status=,directories=no,toolbar=no,resizable=no,menubar=no,scrollbars=no,left=5,top=5'
	nw= window.open(pag,'cualquier_nombre',propi);
	nw= nw.focus();
}
// ffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffff
function emulSubmit(f,doc,modo,id){
	// Esta rutina emula un submit.
	if( modo=="del"){
		if( confirm("Esta seguro(a) de Eliminar el registro seleccionado?")){
			f.action=doc+"?modo="+modo+"&idTupla="+id;
			f.submit();
		}
	}
	else{
		f.action=doc+"?modo="+modo+"&idTupla="+id;
		f.submit();
	}
}
// ffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffff
function closeWindow(){
	opener.document.frm1.submit();
	this.close();
}
// ffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffff
function openInNewTab(URL) {
	var temporalForm = document.createElement('form');
	with (temporalForm) {
		setAttribute('method', 'GET');
		setAttribute('action', URL);
		setAttribute('target', '_blank');
	}

	var paramsString = URL.substring(URL.indexOf('?') + 1, URL.length);
	var paramsArray = paramsString.split('&');

	for (var i = 0; i < paramsArray.length; ++i) {
		var elementIndex = paramsArray[i].indexOf('=');
		var elementName = paramsArray[i].substring(0, elementIndex);
		var elementValue = paramsArray[i].substring(elementIndex + 1, paramsArray[i].length);

		var temporalElement = document.createElement('input');
		with(temporalElement) {
			setAttribute('type', 'hidden');
			setAttribute('name', elementName);
			setAttribute('value', elementValue);
		}

		temporalForm.appendChild(temporalElement);
	}

	document.body.appendChild(temporalForm);
	temporalForm.submit();
	document.body.removeChild(temporalForm);
}
// ffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffff
// * Submit de proposito General *
// Esta funcion ayuda a ejecutar submits dinamicos.
// Para los links (Guardar,Actualizar,Eliminar)
function submitD(f,doc,id,modo){
	if(modo=="eliminar"){
		if( confirm("Esta seguro de eliminar este registro?") ){
			f.action=doc+"?modo=eliminar&idReg="+id;
			f.submit();
		}
	}
	else if(modo=="del"){
		if( confirm("Esta seguro de eliminar este registro?") ){
			f.action=doc+"?modo=del&idReg="+id;
			f.submit();
		}
	}
    else if(modo=="baja"){
        if( confirm("Esta seguro de dar de BAJA este registro?") ){
            f.action=doc+"?modo=baja&idReg="+id;
            f.submit();
        }
    }
    else if(modo=="bajaPedido"){
        if( confirm("Esta seguro de dar de BAJA este PEDIDO?") ){
            f.action=doc+"?modo=bajaPedido&idReg="+id;
            f.submit();
        }
    }
	else{
		f.action=doc+"?modo="+modo+"&idReg="+id;
		f.submit();
	}
}
function delNome(f,doc,idReg,idRelNome){
        if( confirm("Esta seguro de eliminar la nomenclatura?") ){
            f.action=doc+"?modo=delNome&idReg="+idReg+"&idRelNome="+idRelNome;
            f.submit();
        }
}
function delFile(f,doc,opc){
        if( confirm("Esta seguro de eliminar el archivo : ("+ opc +") ?") ){
            f.action=doc+"?modoDel=ok&opc="+opc;
            f.submit();
        }
}

// --------------------------
// Funciones generales
// --------------------------
function openCalendar(pag) {
	propi='width='+300+',height='+200+',left=5,top=500,menubar=yes,scrollbars=yes,location=yes,directories=no,toolbar=no,resizable=yes,menubar=yes,scrollbars=yes,status=yes';
	nw= window.open(pag,'wCalendar',propi);
	nw= nw.focus();
}
function ventanaNueva(pag,ancho,alto) {
	propi='width='+ancho+',height='+alto+',left=5,top=30,menubar=yes,scrollbars=yes,location=yes,directories=no,toolbar=no,resizable=yes,menubar=yes,scrollbars=yes,status=yes';
	//'width=493,height=393,location=0,status=,directories=no,toolbar=no,resizable=no,menubar=no,scrollbars=no,left=5,top=5'
	nw= window.open(pag,'cualquier_nombre',propi);
	nw= nw.focus();
}

function ventanaNueva2(pag,ancho,alto) {
	propi='width='+ancho+',height='+alto+',left=15,top=50,menubar=yes,scrollbars=yes,location=yes,directories=no,toolbar=no,resizable=yes,menubar=yes,scrollbars=yes,status=yes';
	nw= window.open(pag,'ventana2',propi);
	nw= nw.focus();
}
function ventanaNueva3(pag,ancho,alto) {
	propi='width='+ancho+',height='+alto+',left=15,top=50,menubar=yes,scrollbars=yes,location=yes,directories=no,toolbar=no,resizable=yes,menubar=yes,scrollbars=yes,status=yes';
	nw= window.open(pag,'ventana3',propi);
	nw= nw.focus();
}
function selectConte(conte,equipo){
	opener.document.frm1.conte.value=conte;
	opener.document.frm1.equipo.value=equipo;
	window.close();
}
function selectCliente(cliente,op,rfc,dom){
    //var dom;      
    
    if( op=="cli" ){
        opener.document.frm1.cliente.value=cliente;
    }
    if( op=="rem" ){
        opener.document.frm1.remitente.value=cliente;
        opener.document.frm1.remitenteRFC.value=rfc;        
        opener.document.frm1.remitenteDom.value=dom;        
    }
    if( op=="des" ){
        opener.document.frm1.destinatario.value=cliente;
        opener.document.frm1.destinatarioRFC.value=rfc;
        opener.document.frm1.destinatarioDom.value=dom;
    }
    if( op=="aa" ){
        opener.document.frm1.aa.value=cliente;
        opener.document.frm1.aaRFC.value=rfc;
        opener.document.frm1.aaDom.value=dom;
    }
    window.close();    
}
function selectPlaza(localidad,op){
    if(op=="ori"){
        opener.document.frm1.ori.value=localidad;    
    }
    if( op=="des" ){
        opener.document.frm1.des.value=localidad;      
    }    
    window.close();    
}
