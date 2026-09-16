/* Antes: api/solicitar_reset.php */

import { randomBytes } from "node:crypto";
import { sql } from "./_lib/db.js";
import { json, cuerpo, soloMetodo } from "./_lib/http.js";
import { esEmail } from "./_lib/validar.js";
import { avisarAMake } from "./_lib/make.js";

export default async function handler(req, res) {
  if (!soloMetodo(req, res, "POST")) return;

  const email = (cuerpo(req).email || "").trim();

  /* Siempre la misma respuesta, exista o no el email. Si contestáramos distinto,
     cualquiera podría averiguar quién está registrado probando direcciones. */
  const generica = {
    ok: true,
    mensaje: "Si el email está registrado, te llegó un correo con instrucciones."
  };

  if (!esEmail(email)) return json(res, 200, generica);

  const [trabajador] = await sql`
    SELECT provider_id, nombre FROM trabajadores WHERE email = ${email}
  `;

  let tipo = null;
  let identificador = null;
  let nombre = null;

  if (trabajador) {
    tipo = "trabajador";
    identificador = trabajador.provider_id;
    nombre = trabajador.nombre;
  } else {
    const [usuario] = await sql`SELECT id, nombre FROM usuarios WHERE email = ${email}`;
    if (usuario) {
      tipo = "usuario";
      identificador = String(usuario.id);
      nombre = usuario.nombre;
    }
  }

  if (!tipo) return json(res, 200, generica);

  const token = randomBytes(32).toString("hex");

  await sql`
    INSERT INTO password_resets (tipo, identificador, email, token, expira)
    VALUES (${tipo}, ${identificador}, ${email}, ${token}, now() + INTERVAL '1 hour')
  `;

  json(res, 200, generica);

  const base = process.env.SITE_URL || `https://${req.headers.host}`;
  await avisarAMake({
    tipo: "recuperar_password",
    email,
    nombre,
    link: `${base}/restablecer.html?token=${token}`
  });
}
