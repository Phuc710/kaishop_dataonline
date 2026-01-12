/**
 * Animated Particles Background
 * Lightweight particle system without external libraries
 */

class ParticleSystem {
    constructor(canvasId, options = {}) {
        this.canvas = document.getElementById(canvasId);
        if (!this.canvas) return;
        
        this.ctx = this.canvas.getContext('2d');
        this.particles = [];
        
        // Configuration
        this.config = {
            particleCount: options.particleCount || 80,
            particleSize: options.particleSize || 2,
            particleSpeed: options.particleSpeed || 0.5,
            particleColor: options.particleColor || '#8b5cf6',
            lineColor: options.lineColor || '#8b5cf6',
            lineDistance: options.lineDistance || 150,
            lineOpacity: options.lineOpacity || 0.2
        };
        
        this.init();
    }
    
    init() {
        this.resize();
        window.addEventListener('resize', () => this.resize());
        
        // Create particles
        for (let i = 0; i < this.config.particleCount; i++) {
            this.particles.push(this.createParticle());
        }
        
        // Start animation
        this.animate();
    }
    
    resize() {
        this.canvas.width = window.innerWidth;
        this.canvas.height = window.innerHeight;
    }
    
    createParticle() {
        return {
            x: Math.random() * this.canvas.width,
            y: Math.random() * this.canvas.height,
            vx: (Math.random() - 0.5) * this.config.particleSpeed,
            vy: (Math.random() - 0.5) * this.config.particleSpeed,
            size: Math.random() * this.config.particleSize + 1
        };
    }
    
    drawParticle(particle) {
        this.ctx.beginPath();
        this.ctx.arc(particle.x, particle.y, particle.size, 0, Math.PI * 2);
        this.ctx.fillStyle = this.config.particleColor;
        this.ctx.fill();
    }
    
    drawLine(p1, p2, distance) {
        const opacity = (1 - distance / this.config.lineDistance) * this.config.lineOpacity;
        this.ctx.beginPath();
        this.ctx.strokeStyle = this.hexToRgba(this.config.lineColor, opacity);
        this.ctx.lineWidth = 0.5;
        this.ctx.moveTo(p1.x, p1.y);
        this.ctx.lineTo(p2.x, p2.y);
        this.ctx.stroke();
    }
    
    hexToRgba(hex, alpha) {
        const r = parseInt(hex.slice(1, 3), 16);
        const g = parseInt(hex.slice(3, 5), 16);
        const b = parseInt(hex.slice(5, 7), 16);
        return `rgba(${r}, ${g}, ${b}, ${alpha})`;
    }
    
    updateParticle(particle) {
        particle.x += particle.vx;
        particle.y += particle.vy;
        
        // Bounce off edges
        if (particle.x < 0 || particle.x > this.canvas.width) {
            particle.vx *= -1;
        }
        if (particle.y < 0 || particle.y > this.canvas.height) {
            particle.vy *= -1;
        }
        
        // Keep within bounds
        particle.x = Math.max(0, Math.min(this.canvas.width, particle.x));
        particle.y = Math.max(0, Math.min(this.canvas.height, particle.y));
    }
    
    animate() {
        this.ctx.clearRect(0, 0, this.canvas.width, this.canvas.height);
        
        // Update and draw particles
        for (let i = 0; i < this.particles.length; i++) {
            const particle = this.particles[i];
            this.updateParticle(particle);
            this.drawParticle(particle);
            
            // Draw lines to nearby particles
            for (let j = i + 1; j < this.particles.length; j++) {
                const particle2 = this.particles[j];
                const dx = particle.x - particle2.x;
                const dy = particle.y - particle2.y;
                const distance = Math.sqrt(dx * dx + dy * dy);
                
                if (distance < this.config.lineDistance) {
                    this.drawLine(particle, particle2, distance);
                }
            }
        }
        
        requestAnimationFrame(() => this.animate());
    }
}

// Initialize on page load
document.addEventListener('DOMContentLoaded', function() {
    if (document.getElementById('particles-canvas')) {
        new ParticleSystem('particles-canvas', {
            particleCount: 80,
            particleSize: 2,
            particleSpeed: 0.3,
            particleColor: '#8b5cf6',
            lineColor: '#8b5cf6',
            lineDistance: 150,
            lineOpacity: 0.15
        });
    }
});
