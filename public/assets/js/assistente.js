function clock(){document.getElementById('server-clock').textContent=new Date().toLocaleString('pt-BR');} clock(); setInterval(clock,1000);
