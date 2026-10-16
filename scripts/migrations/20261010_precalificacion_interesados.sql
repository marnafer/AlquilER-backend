ALTER TABLE `propiedades`
  ADD COLUMN `requisitos_interesados` JSON DEFAULT NULL;

ALTER TABLE `consultas`
  ADD COLUMN `perfil_interesado` JSON DEFAULT NULL;
