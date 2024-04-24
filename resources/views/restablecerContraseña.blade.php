<style>
  /* Fuente y Tipografía */
  body {
    font-family: 'Montserrat', sans-serif;
    font-size: 16px;
    color: #333;
  }

  /* Diseño y Espaciado */
  .password-form-container {
    display: flex;
    justify-content: center;
    align-items: center;
    min-height: 80vh;
  }

  .password-form {
    max-width: 400px;
    width: 100%;
    padding: 40px;
    background-color: #fff;
    border-radius: 12px;
    box-shadow: 0 4px 16px rgba(0, 0, 0, 0.1);
  }

  .form-title {
    font-size: 24px;
    font-weight: 600;
    text-align: center;
    margin-bottom: 24px;
  }

  .form-group {
    margin-bottom: 20px;
  }

  /* Estilos de los Campos de Entrada */
  .form-control {
    display: block;
    width: 100%;
    padding: 12px 16px;
    font-size: 16px;
    border: 1px solid #ddd;
    border-radius: 6px;
    background-color: #f9f9f9;
    box-shadow: 0 2px 6px rgba(0, 0, 0, 0.05);
    transition: border-color 0.3s ease;
  }

  .form-control:focus {
    outline: none;
    border-color: #007bff;
  }

  .form-control.is-invalid {
    border-color: #dc3545;
    background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 12 12' width='12' height='12' fill='none' stroke='%23dc3545'%3e%3ccircle cx='6' cy='6' r='4.5'/%3e%3cpath stroke-linejoin='round' d='M5.8 3.6h.4L6 6.5z'/%3e%3ccircle cx='6' cy='8.2' r='.6' fill='%23dc3545' stroke='none'/%3e%3c/svg%3e");
    background-repeat: no-repeat;
    background-position: right 0.75rem center;
    background-size: 1.5rem 1.5rem;
  }

  /* Botón de Envío */
  .btn-primary {
    display: block;
    width: 100%;
    padding: 12px 24px;
    font-size: 16px;
    font-weight: 600;
    color: #fff;
    background-color: #007bff;
    border: none;
    border-radius: 6px;
    cursor: pointer;
    transition: background-color 0.3s ease;
  }

  .btn-primary:hover {
    background-color: #0056b3;
  }
</style>

<link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;600&display=swap" rel="stylesheet">

<div class="password-form-container">
  <div class="password-form">
    <h2 class="form-title">Cambiar Contraseña</h2>

    <form method="POST">
      @csrf
      <input type="hidden" name="id" value="{{ $user->id }}">

      <div class="form-group">
        <input type="password" class="form-control @error('password') is-invalid @enderror" name="password" placeholder="Nueva Contraseña" required>
        @error('password')
        <div class="invalid-feedback">{{ $message }}</div>
        @enderror
      </div>

      <div class="form-group">
        <input type="password" class="form-control @error('password_confirmation') is-invalid @enderror" name="password_confirmation" placeholder="Confirmar Contraseña" required>
        @error('password_confirmation')
        <div class="invalid-feedback">{{ $message }}</div>
        @enderror
      </div>

      <button type="submit" class="btn btn-primary">Enviar</button>
    </form>
  </div>
</div>