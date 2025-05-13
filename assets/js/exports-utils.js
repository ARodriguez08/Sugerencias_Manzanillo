// Utilidades para exportación de datos
const ExportUtils = {
    // Exportar a PDF
    exportToPDF: (elementId, filename = "reporte") => {
      const element = document.getElementById(elementId)
      if (!element) {
        console.error("Elemento no encontrado")
        return
      }
  
      // Mostrar mensaje de carga
      Swal.fire({
        title: "Generando PDF",
        text: "Por favor espere...",
        allowOutsideClick: false,
        didOpen: () => {
          Swal.showLoading()
        },
      })
  
      // Configuración para html2pdf
      const opt = {
        margin: 10,
        filename: `${filename}_${new Date().toISOString().slice(0, 10)}.pdf`,
        image: { type: "jpeg", quality: 0.98 },
        html2canvas: { scale: 2, useCORS: true },
        jsPDF: { unit: "mm", format: "a4", orientation: "portrait" },
      }
  
      // Importar html2pdf dinámicamente
      import("https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js")
        .then((module) => {
          const html2pdf = module.default
          html2pdf()
            .from(element)
            .set(opt)
            .save()
            .then(() => {
              Swal.fire({
                title: "¡Éxito!",
                text: "PDF generado correctamente",
                icon: "success",
                timer: 2000,
              })
            })
            .catch((err) => {
              console.error("Error al generar PDF:", err)
              Swal.fire({
                title: "Error",
                text: "No se pudo generar el PDF",
                icon: "error",
              })
            })
        })
        .catch((err) => {
          console.error("Error al cargar html2pdf:", err)
          Swal.fire({
            title: "Error",
            text: "No se pudo cargar la librería de exportación",
            icon: "error",
          })
        })
    },
  
    // Exportar a Excel
    exportToExcel: (tableId, filename = "reporte") => {
      const table = document.getElementById(tableId)
      if (!table) {
        console.error("Tabla no encontrada")
        return
      }
  
      // Mostrar mensaje de carga
      Swal.fire({
        title: "Generando Excel",
        text: "Por favor espere...",
        allowOutsideClick: false,
        didOpen: () => {
          Swal.showLoading()
        },
      })
  
      // Importar SheetJS dinámicamente
      import("https://cdn.sheetjs.com/xlsx-0.19.3/package/dist/xlsx.full.min.js")
        .then((module) => {
          const XLSX = module.default
          // Crear libro de trabajo
          const wb = XLSX.utils.table_to_book(table)
  
          // Guardar archivo
          XLSX.writeFile(wb, `${filename}_${new Date().toISOString().slice(0, 10)}.xlsx`)
  
          Swal.fire({
            title: "¡Éxito!",
            text: "Excel generado correctamente",
            icon: "success",
            timer: 2000,
          })
        })
        .catch((err) => {
          console.error("Error al cargar SheetJS:", err)
          Swal.fire({
            title: "Error",
            text: "No se pudo cargar la librería de exportación",
            icon: "error",
          })
        })
    },
  
    // Exportar datos de gráficos
    exportChartData: (chartInstance, filename = "datos_grafico") => {
      if (!chartInstance) {
        console.error("Instancia de gráfico no proporcionada")
        return
      }
  
      try {
        // Obtener datos del gráfico
        const labels = chartInstance.data.labels
        const datasets = chartInstance.data.datasets
  
        // Crear array para datos
        const data = [["Categoría", ...datasets.map((ds) => ds.label || "Serie")]]
  
        // Llenar datos
        labels.forEach((label, i) => {
          const row = [label]
          datasets.forEach((ds) => {
            row.push(ds.data[i])
          })
          data.push(row)
        })
  
        // Importar SheetJS dinámicamente
        import("https://cdn.sheetjs.com/xlsx-0.19.3/package/dist/xlsx.full.min.js")
          .then((module) => {
            const XLSX = module.default
            // Crear libro de trabajo
            const ws = XLSX.utils.aoa_to_sheet(data)
            const wb = XLSX.utils.book_new()
            XLSX.utils.book_append_sheet(wb, ws, "Datos")
  
            // Guardar archivo
            XLSX.writeFile(wb, `${filename}_${new Date().toISOString().slice(0, 10)}.xlsx`)
  
            Swal.fire({
              title: "¡Éxito!",
              text: "Datos exportados correctamente",
              icon: "success",
              timer: 2000,
            })
          })
          .catch((err) => {
            console.error("Error al cargar SheetJS:", err)
            Swal.fire({
              title: "Error",
              text: "No se pudo cargar la librería de exportación",
              icon: "error",
            })
          })
      } catch (error) {
        console.error("Error al exportar datos del gráfico:", error)
        Swal.fire({
          title: "Error",
          text: "No se pudieron exportar los datos",
          icon: "error",
        })
      }
    },
  }
  
  // Agregar al objeto global window
  window.ExportUtils = ExportUtils
  