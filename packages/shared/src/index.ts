import { z } from "zod";

export const HealthResponseSchema = z.object({ status: z.literal("ok") });
export type HealthResponse = z.infer<typeof HealthResponseSchema>;

export const ContinentSchema = z.enum(["america", "europa", "asia", "oceania", "africa"]);

export const ExamCountrySchema = z.object({
  id: z.string(),
  name: z.string(),
  flag: z.string(),
  continent: ContinentSchema,
  requirements: z.array(z.string()),
});

export const ExamSummarySchema = z.object({
  id: z.string(),
  name: z.string(),
  subtitle: z.string(),
  audience: z.string(),
  format: z.string(),
  noteLabel: z.string(),
  note: z.string(),
});

export const RegionalGuideSchema = z.object({
  id: z.string(),
  country: z.string(),
  requirements: z.array(z.string()),
});

export const PreparationTipSchema = z.object({
  title: z.string(),
  description: z.string(),
});

export const InternationalExamCatalogSchema = z.object({
  countries: z.array(ExamCountrySchema),
  exams: z.array(ExamSummarySchema),
  regionalGuides: z.array(RegionalGuideSchema),
  tips: z.array(PreparationTipSchema),
});

export type Continent = z.infer<typeof ContinentSchema>;
export type ExamCountry = z.infer<typeof ExamCountrySchema>;
export type ExamSummary = z.infer<typeof ExamSummarySchema>;
export type RegionalGuide = z.infer<typeof RegionalGuideSchema>;
export type PreparationTip = z.infer<typeof PreparationTipSchema>;
export type InternationalExamCatalog = z.infer<typeof InternationalExamCatalogSchema>;
