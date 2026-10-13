import { InternationalExamCatalogSchema, type InternationalExamCatalog } from "@daydreaming/shared";

export async function getInternationalExamCatalog(): Promise<InternationalExamCatalog> {
  const response = await fetch("/api/public/international-exams");
  if (!response.ok) {
    throw new Error(`Não foi possível carregar o catálogo (${response.status}).`);
  }

  return InternationalExamCatalogSchema.parse(await response.json());
}
