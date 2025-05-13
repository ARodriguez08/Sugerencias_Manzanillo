// Scripts personalizados para Sugerencias-Manzanillo

document.addEventListener("DOMContentLoaded", () => {
  // Inicializar tooltips de Bootstrap
  var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
  var tooltipList = tooltipTriggerList.map((tooltipTriggerEl) => new bootstrap.Tooltip(tooltipTriggerEl))

  // Inicializar popovers de Bootstrap
  var popoverTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="popover"]'))
  var popoverList = popoverTriggerList.map((popoverTriggerEl) => new bootstrap.Popover(popoverTriggerEl))

  // Validación de formularios
  const forms = document.querySelectorAll(".needs-validation")

  Array.from(forms).forEach((form) => {
    form.addEventListener(
      "submit",
      (event) => {
        if (!form.checkValidity()) {
          event.preventDefault()
          event.stopPropagation()
        }

        form.classList.add("was-validated")
      },
      false,
    )
  })

  // Confirmación de contraseñas
  const passwordField = document.getElementById("password")
  const confirmPasswordField = document.getElementById("confirmar_password")

  if (passwordField && confirmPasswordField) {
    confirmPasswordField.addEventListener("input", () => {
      if (passwordField.value !== confirmPasswordField.value) {
        confirmPasswordField.setCustomValidity("Las contraseñas no coinciden")
      } else {
        confirmPasswordField.setCustomValidity("")
      }
    })

    passwordField.addEventListener("input", () => {
      if (confirmPasswordField.value !== "") {
        if (passwordField.value !== confirmPasswordField.value) {
          confirmPasswordField.setCustomValidity("Las contraseñas no coinciden")
        } else {
          confirmPasswordField.setCustomValidity("")
        }
      }
    })
  }

  // Animación de elementos al hacer scroll
  const animateOnScroll = () => {
    const elements = document.querySelectorAll(".animate-on-scroll")

    elements.forEach((element) => {
      const elementPosition = element.getBoundingClientRect().top
      const windowHeight = window.innerHeight

      if (elementPosition < windowHeight - 50) {
        element.classList.add("fade-in")
      }
    })
  }

  // Aplicar animación al cargar la página
  animateOnScroll()

  // Aplicar animación al hacer scroll
  window.addEventListener("scroll", animateOnScroll)

  // Manejar modales de edición y eliminación
  const editButtons = document.querySelectorAll(".btn-edit")
  editButtons.forEach((button) => {
    button.addEventListener("click", function () {
      const id = this.getAttribute("data-id")
      // Aquí se puede implementar la lógica para cargar los datos del elemento a editar
      console.log("Editar elemento con ID:", id)
    })
  })

  const deleteButtons = document.querySelectorAll(".btn-delete")
  deleteButtons.forEach((button) => {
    button.addEventListener("click", function () {
      const id = this.getAttribute("data-id")
      const name = this.getAttribute("data-name")

      if (confirm(`¿Está seguro que desea eliminar "${name}"?`)) {
        // Aquí se puede implementar la lógica para eliminar el elemento
        console.log("Eliminar elemento con ID:", id)
      }
    })
  })

  // Validación de formulario de sugerencia
  const sugerenciaForm = document.getElementById("sugerenciaForm")
  if (sugerenciaForm) {
    sugerenciaForm.addEventListener("submit", (event) => {
      const titulo = document.getElementById("titulo").value
      const descripcion = document.getElementById("descripcion").value
      const categoria = document.getElementById("categoria_id").value

      if (titulo.length < 5) {
        event.preventDefault()
        alert("El título debe tener al menos 5 caracteres.")
        return false
      }

      if (descripcion.length < 10) {
        event.preventDefault()
        alert("La descripción debe tener al menos 10 caracteres.")
        return false
      }

      if (!categoria) {
        event.preventDefault()
        alert("Por favor, seleccione una categoría.")
        return false
      }

      return true
    })
  }

  // Contador de caracteres para textareas
  const textareas = document.querySelectorAll("textarea[maxlength]")
  textareas.forEach((textarea) => {
    const maxLength = textarea.getAttribute("maxlength")
    const counterElement = document.createElement("div")
    counterElement.className = "text-muted small text-end"
    counterElement.innerHTML = `0/${maxLength} caracteres`
    textarea.parentNode.insertBefore(counterElement, textarea.nextSibling)

    textarea.addEventListener("input", function () {
      const currentLength = this.value.length
      counterElement.innerHTML = `${currentLength}/${maxLength} caracteres`

      if (currentLength >= maxLength * 0.9) {
        counterElement.classList.add("text-danger")
      } else {
        counterElement.classList.remove("text-danger")
      }
    })
  })

  // Inicializar Select2 si está disponible
  if (typeof $.fn.select2 !== "undefined") {
    $(".select2").select2({
      theme: "bootstrap-5",
      width: "100%",
    })
  }

  // Notificaciones
  const notificationBell = document.getElementById("notificationBell")
  if (notificationBell) {
    notificationBell.addEventListener("click", () => {
      // Marcar notificaciones como leídas al hacer clic
      const notificationCount = document.getElementById("notificationCount")
      if (notificationCount) {
        fetch("index.php?page=marcar_notificaciones_leidas", {
          method: "POST",
          headers: {
            "Content-Type": "application/x-www-form-urlencoded",
          },
          body: "csrf_token=" + document.getElementById("csrf_token").value,
        })
          .then((response) => response.json())
          .then((data) => {
            if (data.success) {
              notificationCount.style.display = "none"
            }
          })
          .catch((error) => console.error("Error:", error))
      }
    })
  }

  // Import Bootstrap's JavaScript (if not already included)
  if (typeof bootstrap === "undefined") {
    console.error("Bootstrap JavaScript is not loaded. Make sure it is included in your project.")
  }

  // Import jQuery (if not already included)
  if (typeof $ === "undefined") {
    console.error("jQuery is not loaded. Make sure it is included in your project.")
  }
})
