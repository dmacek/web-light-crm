export type Archetype = 'PROVOZOVNA' | 'VYJEZDOVE_REMESLO' | 'ZAKAZKOVA_VYROBA' | 'OSTATNI'

export type Mood = 'MODERN' | 'TRADITIONAL' | 'BOLD' | 'ELEGANT'

export type LeadStatus = 'NEW' | 'CALL_BACK' | 'RESOLVED'

export type SubscriptionStatus = 'TRIAL' | 'ACTIVE' | 'EXPIRED' | 'CANCELLED'

export type SubscriptionPlan = 'MONTHLY' | 'ANNUAL'

export type CustomDomainStatus = 'NONE' | 'PENDING' | 'ACTIVE' | 'ERROR'

export type AuthProviderType = 'SEZNAM' | 'GOOGLE' | 'APPLE' | 'EMAIL_MAGIC_LINK'

export interface BusinessProfileDTO {
  ico: string
  company_name: string
  street: string
  city: string
  zip: string
  archetype: Archetype
  main_trade_name: string
  subdomain: string
  custom_domain?: string | null
  custom_domain_status: CustomDomainStatus
}

export interface SubscriptionDTO {
  status: SubscriptionStatus
  plan?: SubscriptionPlan | null
  trial_ends_at: string
  current_period_ends_at?: string | null
}

export interface BusinessDTO {
  business_id: string
  email: string
  phone: string
  created_at: string
  subscription: SubscriptionDTO
  business_profile: BusinessProfileDTO
}

export interface ServiceItem {
  id: string
  title: string
  description: string
  price_text: string
  order: number
}

export interface GalleryItem {
  id: string
  image_url: string
  thumbnail_url: string
  caption: string
}

export interface WebContentDTO {
  business_id: string
  version: number
  design: {
    mood: Mood
    color_palette: {
      primary: string
      secondary: string
      background: string
    }
    block_variants: {
      hero: string
      pricing: string
      gallery: string
    }
  }
  content: {
    vacation_banner: {
      active: boolean
      text: string
    }
    services: ServiceItem[]
    gallery: GalleryItem[]
    opening_hours: string
    contact: {
      phone: string
      email: string
      address_visible: boolean
    }
  }
  updated_at?: string | null
}

export interface LeadDTO {
  lead_id: string
  business_id: string
  sender_name: string
  sender_phone: string
  sender_email?: string | null
  message: string
  status: LeadStatus
  created_at: string
  reminder_at?: string | null
}

export interface AresResult {
  ico: string
  company_name: string
  street: string
  city: string
  zip: string
  formatted_address: string
}
