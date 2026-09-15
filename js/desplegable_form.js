/* Desplegables de la Parte 2 del formulario */

const TAXONOMIA = {
  construccion: {
    label: "Construcción y Mantenimiento",
    certificaciones: [
      "Certificación de Competencias Laborales en Albañilería",
      "Taller de Cómputo de Materiales y Presupuesto de Obras",
      "Sin certificación"
    ],
    oficios: {
      albanileria: { label: "Albañilería" },
      colocador: { label: "Colocación de Placas/Revestimiento" },
      pintura: { label: "Pintura" },
      impermiabilizacion: { label: "Impermiabilización" }
    }
  },

  instalaciones: {
    label: "Instalaciones y Servicios Técnicos",
    certificaciones: [
      "Matrícula de Gasista de Unidades Funcionales (Tercera Categoria)",
      "Matrícula de Electricista / Certificación de Instalador RPT",
      "Taller de Protocolos de Seguridad e Instalaciones Básicas",
      "Sin certificación"
    ],
    oficios: {
      gasista: { label: "Gasista" },
      plomeria: { label: "Plomería" },
      electricidad: { label: "Electricidad Domiciliaria" },
      climatizacion: { label: "Climatización" }
    }
  },

  mecanica: {
    label: "Mecánica y Metalúrgica",
    certificaciones: [
      "Certificación de Soldador Básico / Calificado",
      "Taller de Mecánica Ligera e Inyección Electrónica Básica",
      "Sin certificación"
    ],
    oficios: {
      auto: { label: "Mecánica de autos/motos" },
      herreria: { label: "Herrería" },
      soldadura: { label: "Soldadura" }
    }
  },

  gastronomia: {
    label: "Gastronomía y Alimentos",
    certificaciones: [
      "Carnet Oficial de Manipuladores de Alimentos",
      "Taller de Inocuidad y Conservación de Alimentos",
      "Sin certificación"
    ],
    oficios: {
      cocina: { label: "Elaboración de Conservas / Viandas caseras" },
      reposteria: { label: "Repostería" },
      catering: { label: "Rotisería" },
      panaderia: { label: "Panadería" }
    }
  },

  jardineria: {
    label: "Verde y Jardinería",
    certificaciones: [
      "Carnet / Certificación de Operador de Mantenimiento de Espacios Verdes",
      "Taller de Manejo Seguro de Herramientas Térmicas (Motosierras/Bordadoras) y Poda Responsable",
      "Sin certificación"
    ],
    oficios: {
      jardineria: { label: "Jardinería" },
      mantenimiento: { label: "Mantenimiento de Espacios Verdes" },
      poda: { label: "Poda" },
      huerta: { label: "Huerta" }
    }
  },

  cuidado: {
    label: "Cuidado de Personas y Limpieza",
    certificaciones: [
      "Certificado Oficial de Auxiliar en Cuidado de Personas / Gerontológico",
      "Taller de Primeros Auxilios y RCP",
      "Sin certificación"
    ],
    oficios: {
      adultos: { label: "Acompañamiento de Adultos Mayores" },
      infantil: { label: "Cuidado infantil" },
      casas: { label: "Mantenimiento de Casas" }
    }
  },

  textil: {
    label: "Textil y Calzado",
    certificaciones: [
      "Certificación en Operación de Maquinaria Industrial",
      "Taller de Moldería Industrial y Escalado",
      "Sin certificación"
    ],
    oficios: {
      costura: { label: "Costuras / Arreglos de Ropa" },
      confeccion: { label: "Confección de Indumentaria" },
      marroquineria: { label: "Marroquinería" }
    }
  },

  estetica: {
    label: "Estética y Cuidado Personal",
    certificaciones: [
      "Certificación en Bioseguridad e Higiene aplicada a la Estética",
      "Taller de Técnicas Modernas de Barbería y Colorimetría",
      "Sin certificación"
    ],
    oficios: {
      peluqueria: { label: "Peluquería" },
      barberia: { label: "Barbería" },
      manicura: { label: "Manicura" },
      pedicura: { label: "Pedicura" },
      cosmetologia: { label: "Cosmetología" }
    }
  },

  otro: {
    label: "Otro (no está en la lista)",
    oficios: null
  }
};