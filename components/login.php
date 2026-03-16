

<form action="action_page.php" method="post">
    <div class="imgcontainer"> 
      <img src="imagensfoto/img_avatar2.jpg" alt="Avatar" class="avatar" >
      <img src="imagensfoto/img_avatar1.jpg" alt="Avatar" class="avatar" >
    </div>

    <div class="container">
        <div class="input-group">
            <label for="uname"><b>Usuário</b></label>
            <input type="text" placeholder="Enter Username" name="uname" required>
        </div>

        <div class="input-group">
            <label for="psw"><b>Senha</b></label>
            <input type="password" placeholder="Enter Password" name="psw" required>
        </div>

    <div style="margin: 15px 0; display: flex; align-items: center; gap: 8px;">
      <input type="checkbox" name="terms" id="terms" required>
      <label for="terms" style="font-size: 13px; color: #ddc80e; font-weight: bold;">
        Eu aceito as <a href="diretrizes.php" style="color: #ddc80e; text-decoration: underline;">Diretrizes de Segurança e Termo de Responsabilidade</a>
     </label>
   </div>

        <button type="submit" style="background-color: #4CAF50; color: white; padding: 10px; width: 100%; cursor: pointer;  border-radius: 1px;">
            Login
        </button>
    
        <button type="button" onclick="deslogar()" class="cancelbtn" style="color: #f44336; margin-top: 10px; width: 100%; color: white; padding: 10px; cursor: pointer;">
           Sair da Conta
        </button>
    </div> 
 </form>